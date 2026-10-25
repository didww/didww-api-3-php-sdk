<?php

namespace Didww\Prices;

use Didww\Auth\BearerMiddleware;
use Didww\Configuration;
use Didww\Exception\DidwwPricesException;
use Didww\Exception\PricesErrorKind;
use Didww\Internal\Random;
use Didww\Internal\Url;
use Didww\Prices\Internal\ResponseSink;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ResponseException;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * One price list of a prices service: its price file, a gzipped CSV, and its
 * lookup. Requests carry a token for the audience of the service host and
 * use the configuration current at the time of the call. Without a price plan
 * of the service every call fails with PricesErrorKind::PRICE_PLAN_NOT_ASSIGNED.
 *
 * Failed calls throw DidwwPricesException (see getKind()), DidwwAuthException
 * when no token could be obtained, or \InvalidArgumentException for invalid
 * arguments. lookup() throws \UnexpectedValueException when a 200 answer is
 * not a JSON:API document it can parse or holds no prices. Downloads throw
 * \RuntimeException for file errors (temporary file, times, rename) and the
 * exception of a destination stream as is.
 *
 * A 503 answer to a file request whose Retry-After is at most a minute is
 * retried up to twice, after that Retry-After plus a small random delay. The
 * wait blocks the process, so download price files from the command line or a
 * scheduled job rather than within a web request.
 *
 * @template T of VoiceInPrice|VoiceOutPrice|SmsOutPrice|SmsInPrice
 */
final class PricesService
{
    public const MAX_ERROR_BODY_SIZE = 1048576;
    public const LOOKUP_TIMEOUT = 60;
    private const FILE_NAME = 'prices.csv.gz';
    private const LOOKUP_PATH = 'lookup/prices';
    private const MAX_UNAVAILABLE_RETRIES = 2;
    private const MAX_UNAVAILABLE_RETRY_AFTER = 60;
    private const TEMP_FILE_ATTEMPTS = 10;
    private const ERROR_BODY_TIMEOUT = 60;

    /** @var callable(): string */
    private $baseUrl;
    /** @var callable(float): void */
    private $sleep;
    /** @var callable(): float */
    private $random;

    /**
     * @param callable(): string          $baseUrl  returns the service URL
     * @param class-string<T>             $rowClass
     * @param array<string, list<string>> $filters  "required", "optional" and "anyOf" (at least one) filter names
     *
     * @internal use the Didww\Prices accessors
     */
    public function __construct(
        callable $baseUrl,
        private readonly string $audience,
        private readonly string $prefix,
        private readonly string $rowClass,
        private readonly array $filters,
    ) {
        $this->baseUrl = $baseUrl;
        $this->sleep = static function (float $seconds): void {
            if ($seconds > 0) {
                usleep((int) round($seconds * 1e6));
            }
        };
        $this->random = static fn (): float => Random::fraction();
    }

    /**
     * A copy that waits with $sleep and draws jitter from $random.
     *
     * @param callable(float): void $sleep  waits the given seconds
     * @param callable(): float     $random returns a number in [0, 1)
     *
     * @internal for tests
     */
    public function withRetryTiming(callable $sleep, callable $random): self
    {
        $copy = clone $this;
        $copy->sleep = $sleep;
        $copy->random = $random;

        return $copy;
    }

    /**
     * The token audience of the service.
     */
    public function getAudience(): string
    {
        return $this->audience;
    }

    /**
     * The path of the price file under the service URL, for example "sms_out/a2p/prices.csv.gz".
     */
    public function getFilePath(): string
    {
        return $this->prefix.self::FILE_NAME;
    }

    /**
     * The path of the lookup under the service URL, for example "sms_out/a2p/lookup/prices".
     */
    public function getLookupPath(): string
    {
        return $this->prefix.self::LOOKUP_PATH;
    }

    /**
     * The lookup filters the service accepts.
     *
     * @return list<string>
     */
    public function getAllowedFilters(): array
    {
        return array_merge($this->filters['required'] ?? [], $this->filters['optional'] ?? [], $this->filters['anyOf'] ?? []);
    }

    /**
     * Returns the prices matching $filters, such as ['number' => '18005550123'].
     * Null and blank values are dropped. A missing or unknown filter throws
     * \InvalidArgumentException before any request; the formats of the values
     * are checked by the server (PricesErrorKind::BAD_REQUEST). The lookup takes
     * at most LOOKUP_TIMEOUT seconds per request.
     *
     * @param array<string, string|int|float|\Stringable|null> $filters
     *
     * @return PriceLookup<T>
     *
     * @throws DidwwPricesException
     * @throws \Didww\Exception\DidwwAuthException
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException           when a 200 answer cannot be parsed
     */
    public function lookup(array $filters): PriceLookup
    {
        $url = Url::joinV3(($this->baseUrl)(), $this->getLookupPath()).'?'.$this->lookupQuery($filters);
        $sink = new ResponseSink(null, true, self::MAX_ERROR_BODY_SIZE, self::ERROR_BODY_TIMEOUT);
        $response = $this->send('GET', $url, [
            'headers' => ['Accept' => 'application/vnd.api+json'],
            'timeout' => $this->lookupTimeout(),
        ], $sink);
        if (200 !== $response->getStatusCode()) {
            throw self::error($response, $sink->getErrorBody());
        }
        if (!$sink->isComplete($response)) {
            throw self::incompleteBody($response, strlen($sink->getBody()));
        }

        return $this->parseLookup($sink->getBody());
    }

    /**
     * Returns the headers of the price file without its content. A non-null
     * $ifModifiedSince is sent as If-Modified-Since.
     *
     * @throws DidwwPricesException
     * @throws \Didww\Exception\DidwwAuthException
     */
    public function head(?\DateTimeInterface $ifModifiedSince = null): PriceFile
    {
        return $this->requestFile('HEAD', $ifModifiedSince, null);
    }

    /**
     * Downloads the price file exactly as served: gzipped, never decompressed
     * (see PriceFileReader). A non-null $ifModifiedSince is sent as
     * If-Modified-Since; a 304 answer writes nothing.
     *
     * A path is written through a temporary file next to it, which replaces
     * the path only when the download succeeds, so a failed download leaves an
     * existing file as it was. The new file gets the mode of a new file (0666
     * minus the umask) and the Last-Modified of the answer as its modification
     * time.
     *
     * A stream gets only the bytes of a 200 answer, but may hold partial data
     * when the transfer fails.
     *
     * @param string|resource|StreamInterface $to a file path or a writable stream
     *
     * @throws DidwwPricesException
     * @throws \Didww\Exception\DidwwAuthException
     * @throws \InvalidArgumentException           when $to is neither a path nor a stream
     * @throws \RuntimeException                   for file errors
     */
    public function download($to, ?\DateTimeInterface $ifModifiedSince = null): PriceFile
    {
        if (is_string($to) && '' !== $to) {
            return $this->downloadToFile($to, $ifModifiedSince);
        }
        if (is_resource($to) || $to instanceof StreamInterface) {
            return $this->requestFile('GET', $ifModifiedSince, $to);
        }

        throw new \InvalidArgumentException('Destination must be a file path, a stream resource or a StreamInterface');
    }

    /**
     * Downloads the price file to $path like download() unless the file at
     * $path is current: the modification time of an existing file is sent as
     * If-Modified-Since, and a 304 answer leaves the file untouched. It trusts
     * the modification time of any file at $path.
     *
     * @throws DidwwPricesException
     * @throws \Didww\Exception\DidwwAuthException
     * @throws \InvalidArgumentException           when $path is empty
     * @throws \RuntimeException                   for file errors
     */
    public function fetch(string $path): PriceFile
    {
        if ('' === $path) {
            throw new \InvalidArgumentException('Destination must be a file path');
        }
        clearstatcache(true, $path);
        $ifModifiedSince = null;
        if (file_exists($path)) {
            error_clear_last();
            $mtime = @filemtime($path);
            if (false === $mtime) {
                throw new \RuntimeException(error_get_last()['message'] ?? 'Failed to read the modification time of '.$path);
            }
            $ifModifiedSince = new \DateTimeImmutable('@'.$mtime);
        }

        return $this->downloadToFile($path, $ifModifiedSince);
    }

    private function downloadToFile(string $path, ?\DateTimeInterface $ifModifiedSince): PriceFile
    {
        [$tempPath, $handle] = self::createTempFile($path);
        try {
            $file = $this->requestFile('GET', $ifModifiedSince, $handle);
            if ($file->isNotModified()) {
                return $file;
            }
            // Streams of other wrappers (user-space ones) cannot be synced.
            $plainFile = 'plainfile' === (stream_get_meta_data($handle)['wrapper_type'] ?? null);
            error_clear_last();
            if (!@fflush($handle) || ($plainFile && !@fsync($handle)) || !@fclose($handle)) {
                throw new \RuntimeException(error_get_last()['message'] ?? 'Failed to write '.$tempPath);
            }
            $lastModified = $file->getLastModified();
            // A user-space wrapper may not support setting the modification time.
            if (null !== $lastModified && !@touch($tempPath, $lastModified->getTimestamp(), $lastModified->getTimestamp()) && $plainFile) {
                throw new \RuntimeException(error_get_last()['message'] ?? 'Failed to set the modification time of '.$tempPath);
            }
            error_clear_last();
            if (!@rename($tempPath, $path)) {
                throw new \RuntimeException(error_get_last()['message'] ?? 'Failed to rename '.$tempPath.' to '.$path);
            }

            return $file;
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    // Creates a new file next to $path with a random suffix. Unlike tempnam(),
    // which uses mode 0600, the file gets 0666 minus the umask.
    private static function createTempFile(string $path): array
    {
        for ($attempt = 1;; ++$attempt) {
            $tempPath = $path.'.'.bin2hex(random_bytes(8)).'.tmp';
            error_clear_last();
            $handle = @fopen($tempPath, 'xb');
            if (false !== $handle) {
                return [$tempPath, $handle];
            }
            $message = error_get_last()['message'] ?? 'Failed to create '.$tempPath;
            if ($attempt >= self::TEMP_FILE_ATTEMPTS || !file_exists($tempPath)) {
                throw new \RuntimeException($message);
            }
        }
    }

    // Sends a HEAD or GET for the price file, retrying a 503 whose Retry-After
    // is at most a minute up to MAX_UNAVAILABLE_RETRIES times.
    private function requestFile(string $method, ?\DateTimeInterface $ifModifiedSince, mixed $destination): PriceFile
    {
        $url = Url::joinV3(($this->baseUrl)(), $this->getFilePath());
        $headers = null === $ifModifiedSince ? [] : ['If-Modified-Since' => self::httpDate($ifModifiedSince)];
        for ($attempt = 1;; ++$attempt) {
            $sink = new ResponseSink('GET' === $method ? $destination : null, false, self::MAX_ERROR_BODY_SIZE, self::ERROR_BODY_TIMEOUT);
            $response = $this->send($method, $url, ['headers' => $headers], $sink);
            $status = $response->getStatusCode();
            if ('GET' === $method && 200 === $status && !$sink->isComplete($response)) {
                throw self::incompleteBody($response, $sink->getBytesWritten());
            }
            if (200 === $status || 304 === $status) {
                return PriceFile::fromResponse($response, 200 === $status ? $sink->getBytesWritten() : 0);
            }
            $retryAfter = self::retryAfter($response);
            if (503 === $status && null !== $retryAfter && $attempt <= self::MAX_UNAVAILABLE_RETRIES && $retryAfter <= self::MAX_UNAVAILABLE_RETRY_AFTER) {
                ($this->sleep)($this->spread(max($retryAfter, 1)));
                continue;
            }

            throw self::error($response, $sink->getErrorBody());
        }
    }

    // Sends a request through the authenticating client with $sink as its sink.
    // Transport failures become CONNECTION_ERROR, except an answer other than
    // 200 whose body could not be read in full, which is returned for mapping
    // (its body as far as it was read). The answer of a failed transfer is
    // that of a Guzzle ResponseException, or else the one on_stats saw.
    private function send(string $method, string $url, array $options, ResponseSink $sink): ResponseInterface
    {
        $client = Configuration::getHttpClient();
        $clientOnHeaders = $client instanceof \GuzzleHttp\Client ? $client->getConfig('on_headers') : null;
        $clientOnStats = $client instanceof \GuzzleHttp\Client ? $client->getConfig('on_stats') : null;
        $statsResponse = null;
        $options += [
            BearerMiddleware::AUDIENCE_OPTION => $this->audience,
            'sink' => $sink,
            'on_headers' => static function ($response, #[\SensitiveParameter] ...$more) use ($sink, $clientOnHeaders): void {
                if ($response instanceof ResponseInterface) {
                    $sink->begin($response);
                }
                if (is_callable($clientOnHeaders)) {
                    $clientOnHeaders($response, ...$more);
                }
            },
            'on_stats' => static function (#[\SensitiveParameter] TransferStats $stats, #[\SensitiveParameter] ...$more) use (&$statsResponse, $clientOnStats): void {
                $statsResponse = $stats->getResponse();
                if (is_callable($clientOnStats)) {
                    $clientOnStats($stats, ...$more);
                }
            },
            'allow_redirects' => false,
            'http_errors' => false,
            'decode_content' => false,
        ];
        // A streamed answer would bypass the sink.
        $options['stream'] = false;
        try {
            $response = $client->request($method, $url, $options);
        } catch (GuzzleException $e) {
            return self::answerOfFailedTransfer($e, $sink, $statsResponse);
        }
        if (null !== $sink->getWriteError()) {
            throw $sink->getWriteError();
        }

        return $response;
    }

    // The answer of a transfer that failed, for mapping, when it is not a 200
    // and its sink took no write error; otherwise throws.
    private static function answerOfFailedTransfer(#[\SensitiveParameter] GuzzleException $e, ResponseSink $sink, ?ResponseInterface $statsResponse): ResponseInterface
    {
        if (null !== $sink->getWriteError()) {
            throw $sink->getWriteError();
        }
        $response = ($e instanceof ResponseException ? $e->getResponse() : null) ?? $statsResponse;
        if (null === $response || 200 === $response->getStatusCode()) {
            throw new DidwwPricesException(PricesErrorKind::CONNECTION_ERROR, $response?->getStatusCode(), previous: $e);
        }
        if ($response !== $sink->getResponse()) {
            $sink->begin($response);
        }

        return $response;
    }

    // The lookup timeout: LOOKUP_TIMEOUT, or the client's own total timeout when shorter.
    private function lookupTimeout(): float
    {
        $client = Configuration::getHttpClient();
        $timeout = $client instanceof \GuzzleHttp\Client ? $client->getConfig('timeout') : null;

        return is_numeric($timeout) && $timeout > 0 ? min((float) $timeout, self::LOOKUP_TIMEOUT) : self::LOOKUP_TIMEOUT;
    }

    // Checks $filters against the filters of the service and encodes them.
    private function lookupQuery(array $filters): string
    {
        $values = [];
        foreach ($filters as $name => $value) {
            if (null === $value) {
                continue;
            }
            if (!is_string($value) && !is_int($value) && !is_float($value) && !$value instanceof \Stringable) {
                throw new \InvalidArgumentException(sprintf('Filter %s must be a string, got %s', $name, get_debug_type($value)));
            }
            $value = (string) $value;
            if ('' !== trim($value)) {
                $values[(string) $name] = $value;
            }
        }
        $names = array_map('strval', array_keys($values));
        sort($names, SORT_STRING);

        $allowed = $this->getAllowedFilters();
        $unknown = array_values(array_diff($names, $allowed));
        $missing = array_values(array_diff($this->filters['required'] ?? [], $names));
        $anyOf = $this->filters['anyOf'] ?? [];
        if ($unknown) {
            throw new \InvalidArgumentException(sprintf('Filter %s is not allowed on %s (allowed: %s)', implode(', ', $unknown), $this->getLookupPath(), implode(', ', $allowed)));
        }
        if ($missing) {
            throw new \InvalidArgumentException(sprintf('Filter %s is required on %s', implode(', ', $missing), $this->getLookupPath()));
        }
        if ($anyOf && !array_intersect($anyOf, $names)) {
            throw new \InvalidArgumentException(sprintf('One of filters %s is required on %s', implode(', ', $anyOf), $this->getLookupPath()));
        }

        $pairs = [];
        foreach ($names as $name) {
            $pairs[] = rawurlencode('filter['.$name.']').'='.rawurlencode($values[$name]);
        }

        return implode('&', $pairs);
    }

    /**
     * @return PriceLookup<T>
     */
    private function parseLookup(string $body): PriceLookup
    {
        try {
            $document = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \UnexpectedValueException('Failed to parse price lookup: '.$e->getMessage(), 0, $e);
        }
        $data = is_array($document) ? ($document['data'] ?? null) : null;
        if (!is_array($data) || !array_is_list($data)) {
            throw new \UnexpectedValueException('Failed to parse price lookup: the answer holds no data array');
        }
        if (!$data) {
            throw new \UnexpectedValueException('Failed to parse price lookup: the answer holds no prices');
        }
        $rows = [];
        foreach ($data as $resource) {
            if (!is_array($resource)) {
                throw new \UnexpectedValueException('Failed to parse price lookup: a row is not an object');
            }
            try {
                $rows[] = ($this->rowClass)::fromJsonApi($resource);
            } catch (\UnexpectedValueException $e) {
                throw new \UnexpectedValueException('Failed to parse price lookup: '.$e->getMessage(), 0, $e);
            }
        }
        $meta = $document['meta'] ?? [];
        if (!is_array($meta) || !is_int($meta['total_records'] ?? 0)) {
            throw new \UnexpectedValueException('Failed to parse price lookup meta: total_records is not an integer');
        }

        return new PriceLookup($rows, $meta['total_records'] ?? 0);
    }

    // Maps an answer other than a success to a DidwwPricesException.
    private static function error(ResponseInterface $response, string $body): DidwwPricesException
    {
        $status = $response->getStatusCode();
        $errors = [];
        $document = json_decode($body, true);
        if (is_array($document) && is_array($document['errors'] ?? null)) {
            foreach ($document['errors'] as $error) {
                if (is_array($error) && !array_is_list($error)) {
                    $errors[] = $error;
                }
            }
        }
        $code = isset($errors[0]['code']) && is_scalar($errors[0]['code']) ? (string) $errors[0]['code'] : null;
        $kind = match ($status) {
            400 => PricesErrorKind::BAD_REQUEST,
            401 => PricesErrorKind::NOT_AUTHORIZED,
            403 => PricesErrorKind::ACCESS_DENIED,
            404 => 'price_not_found' === $code ? PricesErrorKind::PRICE_NOT_FOUND : PricesErrorKind::PRICE_PLAN_NOT_ASSIGNED,
            406 => PricesErrorKind::NOT_ACCEPTABLE,
            429 => PricesErrorKind::TOO_MANY_REQUESTS,
            503 => PricesErrorKind::SERVICE_UNAVAILABLE,
            default => PricesErrorKind::UNEXPECTED_STATUS,
        };
        if (404 === $status && (null === $code || '' === $code)) {
            $code = 'price_plan_not_assigned';
        }

        return new DidwwPricesException($kind, $status, $code, $errors, self::retryAfter($response));
    }

    // The Retry-After of an answer given in seconds; an HTTP date is ignored.
    private static function retryAfter(ResponseInterface $response): ?int
    {
        $value = trim($response->getHeaderLine('Retry-After'));

        return preg_match('/\A\d{1,9}\z/', $value) ? (int) $value : null;
    }

    // $retryAfter plus a random extra of up to a fifth of it, or up to a second
    // when that is more, so that clients told the same Retry-After do not come
    // back in step.
    private function spread(int $retryAfter): float
    {
        return $retryAfter + ($this->random)() * max(1, $retryAfter / 5);
    }

    private static function httpDate(\DateTimeInterface $date): string
    {
        return gmdate('D, d M Y H:i:s', $date->getTimestamp()).' GMT';
    }

    private static function incompleteBody(ResponseInterface $response, int $received): DidwwPricesException
    {
        return new DidwwPricesException(PricesErrorKind::CONNECTION_ERROR, 200, previous: new \RuntimeException(
            sprintf('incomplete body: %d of %s bytes', $received, $response->getHeaderLine('Content-Length'))
        ));
    }
}
