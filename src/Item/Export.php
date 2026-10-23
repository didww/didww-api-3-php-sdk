<?php

namespace Didww\Item;

use Didww\Enum\CallbackMethod;
use Didww\Enum\ExportStatus;
use Didww\Enum\ExportTrunkType;
use Didww\Enum\ExportType;
use Didww\Internal\GunzipStream;
use Didww\Internal\Url;
use Didww\Prices\Internal\ResponseSink;
use Didww\Traits\Fetchable;
use Didww\Traits\HasExternalReferenceId;
use Didww\Traits\Saveable;
use GuzzleHttp\Exception\ResponseException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\ResponseInterface;

/**
 * Responses for exports created at an earlier API version may carry the
 * `year`, `month` and `day` filters of that version.
 */
class Export extends BaseItem
{
    use Saveable;
    use Fetchable;
    use HasExternalReferenceId;

    private const MAX_ERROR_BODY_SIZE = 1048576;
    private const ERROR_BODY_TIMEOUT = 60;

    public static function getEndpoint(): string
    {
        return '/exports';
    }

    protected $type = 'exports';

    protected $visible = [
        'filters',
        'export_type',
        'callback_url',
        'callback_method',
        'external_reference_id',
    ];

    private $filters = [];

    public function setFilterDidNumber($didNumber)
    {
        $this->filters['did_number'] = $didNumber;
    }

    /**
     * Start of the exported period (inclusive), as `YYYY-MM-DD HH:MM:SS` in UTC.
     * Required for every export type, like setFilterTo().
     */
    public function setFilterFrom(string $from)
    {
        $this->filters['from'] = $from;
    }

    /** End of the exported period (exclusive), as `YYYY-MM-DD HH:MM:SS` in UTC. */
    public function setFilterTo(string $to)
    {
        $this->filters['to'] = $to;
    }

    public function setFilterVoiceOutTrunkId($voiceOutTrunkId)
    {
        $this->filters['voice_out_trunk.id'] = $voiceOutTrunkId;
    }

    /** cdr_in: calls from this caller number, as it reached DIDWW. */
    public function setFilterSourceNumber(string $sourceNumber)
    {
        $this->filters['source_number'] = $sourceNumber;
    }

    /** cdr_in: calls from this caller number, as delivered to the trunk. */
    public function setFilterSourceNumberOut(string $sourceNumberOut)
    {
        $this->filters['source_number_out'] = $sourceNumberOut;
    }

    /** cdr_in: calls from the country with this ISO 3166-1 alpha-2 code. */
    public function setFilterSourceCountryIso(string $sourceCountryIso)
    {
        $this->filters['source_country_iso'] = $sourceCountryIso;
    }

    /** cdr_out: calls from this source number. */
    public function setFilterSource(string $source)
    {
        $this->filters['source'] = $source;
    }

    /** cdr_out: calls presenting this caller ID. */
    public function setFilterCli(string $cli)
    {
        $this->filters['cli'] = $cli;
    }

    /** cdr_out: calls to this number. */
    public function setFilterDestination(string $destination)
    {
        $this->filters['destination'] = $destination;
    }

    /** sms_in, sms_out: messages from exactly this sender address. */
    public function setFilterSourceAddressEq(string $sourceAddress)
    {
        $this->filters['source_address_eq'] = $sourceAddress;
    }

    /** sms_in, sms_out: messages whose sender address contains this value. */
    public function setFilterSourceAddressCont(string $sourceAddress)
    {
        $this->filters['source_address_cont'] = $sourceAddress;
    }

    /** sms_in, sms_out: messages to exactly this recipient address. */
    public function setFilterDestinationAddressEq(string $destinationAddress)
    {
        $this->filters['destination_address_eq'] = $destinationAddress;
    }

    /** sms_in, sms_out: messages whose recipient address contains this value. */
    public function setFilterDestinationAddressCont(string $destinationAddress)
    {
        $this->filters['destination_address_cont'] = $destinationAddress;
    }

    /**
     * sms_in, sms_out: messages in this state, e.g. `Success`, `Failed`,
     * `Queued` (sms_in) or `Routing Error`, `In Progress` (sms_out).
     */
    public function setFilterStatus(string $status)
    {
        $this->filters['status'] = $status;
    }

    /**
     * sms_in, sms_out: messages through this SMS trunk, or any of these.
     *
     * @param string|string[] $smsTrunkId
     */
    public function setFilterSmsTrunkId(string|array $smsTrunkId)
    {
        $this->filters['sms_trunk.id'] = is_array($smsTrunkId) ? array_values($smsTrunkId) : $smsTrunkId;
    }

    /** sms_in, sms_out: the message with this identifier. */
    public function setFilterSmsId(string $smsId)
    {
        $this->filters['sms_id'] = $smsId;
    }

    /**
     * sms_in: messages delivered through trunks of this type, or of any of these.
     *
     * @param ExportTrunkType|string|array<ExportTrunkType|string> $trunkType
     */
    public function setFilterTrunkType(ExportTrunkType|string|array $trunkType)
    {
        $this->filters['trunk_type'] = is_array($trunkType)
            ? array_values(array_map(static fn ($v) => $v instanceof \BackedEnum ? $v->value : $v, $trunkType))
            : ($trunkType instanceof \BackedEnum ? $trunkType->value : $trunkType);
    }

    /** sms_out: `a2p` or `p2p` traffic, in any letter case. */
    public function setFilterServiceType(string $serviceType)
    {
        $this->filters['service_type'] = $serviceType;
    }

    /**
     * sms_out: messages sent under this sender ID verification, or any of these.
     *
     * @param string|string[] $senderIdVerificationId
     */
    public function setFilterSenderIdVerificationId(string|array $senderIdVerificationId)
    {
        $this->filters['sender_id_verification.id'] = is_array($senderIdVerificationId) ? array_values($senderIdVerificationId) : $senderIdVerificationId;
    }

    /**
     * Sets any filter key, for keys without a dedicated setter.
     */
    public function setFilter(string $key, mixed $value)
    {
        $this->filters[$key] = $value instanceof \BackedEnum ? $value->value : $value;
    }

    public function toJsonApiArray(): array
    {
        $data = parent::toJsonApiArray();
        $data['attributes']['filters'] = $this->filters;

        return $data;
    }

    public function getStatus(): ExportStatus
    {
        return $this->enumAttribute('status', ExportStatus::class);
    }

    public function isPending(): bool
    {
        return ExportStatus::PENDING === $this->getStatus();
    }

    public function isProcessing(): bool
    {
        return ExportStatus::PROCESSING === $this->getStatus();
    }

    public function isCompleted(): bool
    {
        return ExportStatus::COMPLETED === $this->getStatus();
    }

    public function getExportType(): ExportType
    {
        return $this->enumAttribute('export_type', ExportType::class);
    }

    public function setExportType(ExportType|string $exportType)
    {
        $this->setEnumAttribute('export_type', $exportType);
    }

    public function getCallbackUrl(): ?string
    {
        return $this->attribute('callback_url');
    }

    public function setCallbackUrl(string $callbackUrl)
    {
        $this->attributes['callback_url'] = $callbackUrl;
    }

    public function getCallbackMethod(): ?CallbackMethod
    {
        return $this->enumAttribute('callback_method', CallbackMethod::class);
    }

    public function setCallbackMethod(CallbackMethod|string $callbackMethod)
    {
        $this->setEnumAttribute('callback_method', $callbackMethod);
    }

    /**
     * Downloads the export file (gzip-compressed CSV) to $dest unchanged.
     *
     * The URL must be on the origin (scheme, host and port) of the API base URL
     * or of the environment, so that the access token is not sent elsewhere.
     * Only a 200 answer is written; redirects are not followed. The body of
     * any other answer is read for the error message up to 1 MiB within 60
     * seconds. A path $dest is replaced atomically and left untouched on
     * failure.
     *
     * @param string|resource $dest a file path or a writable stream
     *
     * @return true|string true, or an error message when the download failed
     *
     * @throws \InvalidArgumentException           when the URL is missing or on another origin
     * @throws \Didww\Exception\DidwwAuthException when no access token can be obtained
     */
    public function download($dest)
    {
        $url = $this->downloadUrl();
        $tmpFile = false;
        $handle = false;
        try {
            if (is_resource($dest)) {
                $tmpFile = tempnam(sys_get_temp_dir(), 'didww_export_');
                $handle = false === $tmpFile ? false : @fopen($tmpFile, 'w+b');
                if (false === $handle) {
                    return 'Failed to create temporary file';
                }
            } else {
                // Next to $dest, so that the rename is atomic; created first, so
                // that its mode follows the umask and a missing directory shows.
                $tmpFile = $dest.'.'.bin2hex(random_bytes(8)).'.tmp';
                $handle = @fopen($tmpFile, 'xb');
                if (false === $handle) {
                    $tmpFile = false;

                    return 'Failed to open destination file for writing';
                }
            }

            $result = self::fetch($url, $handle);
            if (true !== $result) {
                return $result;
            }
            if (!is_resource($dest)) {
                if (!fclose($handle)) {
                    return 'Failed to write the export to '.$dest;
                }

                return @rename($tmpFile, $dest) ? true : 'Failed to write the export to '.$dest;
            }
            rewind($handle);
            $copied = stream_copy_to_stream($handle, $dest);

            return false === $copied ? 'Failed to write the export to the destination stream' : true;
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if (false !== $tmpFile && file_exists($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }

    /**
     * Downloads the export file like download() and writes it to $dest
     * decompressed (CSV).
     *
     * A body that is not gzip, is corrupt or is cut short is an error. A path
     * $dest is replaced atomically and left untouched on failure; a stream
     * $dest is written only after the whole file has been decompressed.
     *
     * @param string|resource $dest a file path or a writable stream
     *
     * @return true|string true, or an error message when the download or the decompression failed
     *
     * @throws \InvalidArgumentException           when the URL is missing or on another origin
     * @throws \Didww\Exception\DidwwAuthException when no access token can be obtained
     */
    public function downloadAndDecompress($dest)
    {
        $url = $this->downloadUrl();
        $ownHandle = !is_resource($dest);
        if ($ownHandle) {
            // Next to $dest, so that the rename is atomic; created first, so
            // that its mode follows the umask and a missing directory shows.
            $csvFile = $dest.'.'.bin2hex(random_bytes(8)).'.tmp';
            $handle = @fopen($csvFile, 'x');
            if (false === $handle) {
                return 'Failed to open destination file for writing';
            }
            fclose($handle);
        } else {
            $csvFile = tempnam(sys_get_temp_dir(), 'didww_export_');
            if (false === $csvFile) {
                return 'Failed to create temporary file';
            }
        }
        // tempnam() creates it with mode 0600, which the download keeps.
        $gzFile = tempnam(sys_get_temp_dir(), 'didww_export_');

        try {
            $gz = false === $gzFile ? false : @fopen($gzFile, 'r+b');
            if (false === $gz) {
                return 'Failed to create temporary file';
            }
            try {
                $result = self::fetch($url, $gz);
            } finally {
                fclose($gz);
            }
            if (true !== $result) {
                return $result;
            }
            $result = self::gunzipFile($gzFile, $csvFile);
            if (true !== $result) {
                return $result;
            }
            if ($ownHandle) {
                return @rename($csvFile, $dest) ? true : 'Failed to write the export to '.$dest;
            }
            $source = @fopen($csvFile, 'rb');
            if (false === $source) {
                return 'Failed to read the decompressed export';
            }
            $copied = stream_copy_to_stream($source, $dest);
            fclose($source);

            return false === $copied ? 'Failed to write the export to the destination stream' : true;
        } finally {
            foreach ([$gzFile, $csvFile] as $file) {
                if (false !== $file && file_exists($file)) {
                    @unlink($file);
                }
            }
        }
    }

    // The download URL, checked by checkDownloadUrl().
    private function downloadUrl(): string
    {
        $url = $this->getAttributes()['url'] ?? null;
        if (!is_string($url) || '' === $url) {
            throw new \InvalidArgumentException('Export has no download URL');
        }
        self::checkDownloadUrl($url);

        return $url;
    }

    // Sends the download request and writes the body of a 200 answer to
    // $handle; returns true or an error message. The body of any other answer
    // is read up to MAX_ERROR_BODY_SIZE bytes within ERROR_BODY_TIMEOUT
    // seconds, after which the transfer is aborted.
    private static function fetch(string $url, $handle): true|string
    {
        $sink = new ResponseSink($handle, false, self::MAX_ERROR_BODY_SIZE, self::ERROR_BODY_TIMEOUT);
        $client = \Didww\Configuration::getHttpClient();
        $clientOnHeaders = $client instanceof \GuzzleHttp\Client ? $client->getConfig('on_headers') : null;
        $clientOnStats = $client instanceof \GuzzleHttp\Client ? $client->getConfig('on_stats') : null;
        $statsResponse = null;
        try {
            $response = $client->request('GET', $url, [
                'sink' => $sink,
                'on_headers' => static function ($response, #[\SensitiveParameter] ...$more) use ($sink, $clientOnHeaders): void {
                    if ($response instanceof ResponseInterface) {
                        $sink->begin($response);
                    }
                    if (is_callable($clientOnHeaders)) {
                        $clientOnHeaders($response, ...$more);
                    }
                },
                // The answer of a transfer that fails after its headers.
                'on_stats' => static function (#[\SensitiveParameter] TransferStats $stats, #[\SensitiveParameter] ...$more) use (&$statsResponse, $clientOnStats): void {
                    $statsResponse = $stats->getResponse();
                    if (is_callable($clientOnStats)) {
                        $clientOnStats($stats, ...$more);
                    }
                },
                'stream' => false,
                'allow_redirects' => false,
                'http_errors' => false,
                'decode_content' => false,
                'cookies' => false,
            ]);
        } catch (TransferException $e) {
            $response = ($e instanceof ResponseException ? $e->getResponse() : null) ?? $statsResponse;
            if (null === $sink->getWriteError() && (null === $response || 200 === $response->getStatusCode())) {
                return 'Export download failed: '.$e->getMessage();
            }
        }
        if (null !== $sink->getWriteError()) {
            return 'Failed to write the downloaded export';
        }
        $status = $response->getStatusCode();
        if (200 === $status && !$sink->isComplete($response)) {
            return 'Export download failed: incomplete body';
        }

        return 200 === $status ? true : self::statusError($status, $sink->getErrorBody());
    }

    // Decompresses the gzip file $gzFile into $csvFile, keeping the bytes as
    // they are; returns true or an error message.
    private static function gunzipFile(string $gzFile, string $csvFile): true|string
    {
        $input = @fopen($gzFile, 'rb');
        if (false === $input) {
            return 'Failed to read the downloaded export';
        }
        $output = @fopen($csvFile, 'wb');
        if (false === $output) {
            fclose($input);

            return 'Failed to open destination file for writing';
        }
        $stream = null;
        try {
            $stream = GunzipStream::open($input, 'Export decompression failed: ', true);
            $copied = stream_copy_to_stream($stream, $output);

            return false === $copied || !fflush($output) ? 'Failed to write the decompressed export' : true;
        } catch (\UnexpectedValueException $e) {
            return $e->getMessage();
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            fclose($output);
            fclose($input);
        }
    }

    // Refuses a download URL whose origin is neither the origin of the API base
    // URL nor that of the environment: the API builds export URLs on the
    // environment host even when the base URL is overridden.
    private static function checkDownloadUrl(string $url): void
    {
        $baseUri = \Didww\Configuration::getBaseUri();
        $environmentUri = \Didww\Configuration::getCredentials()->getEndpoint();
        if (Url::sameOrigin($url, $baseUri) || Url::sameOrigin($url, $environmentUri)) {
            return;
        }

        throw new \InvalidArgumentException(sprintf('Export URL %s does not match the API base URL %s or the environment URL %s', Url::origin($url), Url::origin($baseUri), Url::origin($environmentUri)));
    }

    private static function statusError(int $status, string $body): string
    {
        $message = 'Export download failed with status '.$status;
        $document = json_decode($body, true);
        $details = [];
        foreach (is_array($document) && is_array($document['errors'] ?? null) ? $document['errors'] : [] as $error) {
            $detail = is_array($error) ? ($error['detail'] ?? $error['title'] ?? null) : null;
            if (is_string($detail) && '' !== $detail) {
                $details[] = $detail;
            }
        }

        return $details ? $message.': '.implode('; ', $details) : $message;
    }
}
