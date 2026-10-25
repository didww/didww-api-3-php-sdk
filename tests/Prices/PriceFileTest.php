<?php

namespace Didww\Tests\Prices;

use Didww\Auth\Audience;
use Didww\Auth\TokenProvider;
use Didww\Exception\DidwwPricesException;
use Didww\Exception\PricesErrorKind;
use Didww\Prices;
use Didww\Prices\PricesService;
use Didww\Tests\Prices\Support\NoMetadataStreamWrapper;
use Didww\Tests\Prices\Support\PricesHelpers;
use Didww\Tests\Support\TraceArgs;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\TransferStats;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class PriceFileTest extends TestCase
{
    use PricesHelpers;

    private const LAST_MODIFIED_TIMESTAMP = 1791957600;
    private const OTHER_LAST_MODIFIED_TIMESTAMP = 1792044000;

    private string $dir;
    private string $gzip;
    /** @var list<float> */
    private array $sleeps = [];

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/didww_prices_test_'.bin2hex(random_bytes(6));
        mkdir($this->dir);
        $this->gzip = self::fixture('voice_in_prices.csv.gz');
    }

    protected function tearDown(): void
    {
        TokenProvider::forgetRateLimits();
        foreach ($this->dirEntries() as $entry) {
            unlink($this->path($entry));
        }
        rmdir($this->dir);
    }

    private function path(string $name = 'voice_in_prices.csv.gz'): string
    {
        return $this->dir.'/'.$name;
    }

    /**
     * @return list<string>
     */
    private function dirEntries(): array
    {
        $entries = array_values(array_diff(scandir($this->dir), ['.', '..']));
        sort($entries);

        return $entries;
    }

    private function voiceIn(float $jitter = 0.0): PricesService
    {
        return Prices::voiceIn()->withRetryTiming(function (float $seconds): void {
            $this->sleeps[] = $seconds;
        }, static fn (): float => $jitter);
    }

    private function memory(): StreamInterface
    {
        return Utils::streamFor(fopen('php://memory', 'w+b'));
    }

    private function expectPricesError(callable $call, PricesErrorKind $kind): DidwwPricesException
    {
        try {
            $call();
        } catch (DidwwPricesException $e) {
            $this->assertSame($kind, $e->getKind(), $e->getMessage());

            return $e;
        }
        $this->fail('expected a DidwwPricesException');
    }

    public static function endpointProvider(): array
    {
        return [
            'Voice IN' => ['voiceIn', 'https://api-vin-sandbox.didww.com/v3/prices.csv.gz', Audience::VOICE_IN],
            'Voice OUT' => ['voiceOut', 'https://api-vout-sandbox.didww.com/v3/prices.csv.gz', Audience::VOICE_OUT],
            'SMS OUT A2P' => ['smsOutA2p', 'https://api-sms-sandbox.didww.com/v3/sms_out/a2p/prices.csv.gz', Audience::SMS],
            'SMS OUT P2P' => ['smsOutP2p', 'https://api-sms-sandbox.didww.com/v3/sms_out/p2p/prices.csv.gz', Audience::SMS],
            'SMS OUT OTP' => ['smsOutOtp', 'https://api-sms-sandbox.didww.com/v3/sms_out/otp/prices.csv.gz', Audience::SMS],
            'SMS IN' => ['smsIn', 'https://api-sms-sandbox.didww.com/v3/sms_in/prices.csv.gz', Audience::SMS],
        ];
    }

    #[DataProvider('endpointProvider')]
    public function testEndpoints(string $service, string $url, string $audience)
    {
        $this->configureMock()->append(self::tokenFor($audience), self::priceFile($this->gzip));
        $stream = $this->memory();

        Prices::$service()->download($stream);

        $requests = $this->serviceRequests();
        $this->assertCount(1, $requests);
        $this->assertSame('GET', $requests[0]->getMethod());
        $this->assertSame($url, (string) $requests[0]->getUri());
        $this->assertSame('Bearer token-1', $requests[0]->getHeaderLine('Authorization'));
        $this->assertSame([$audience], $this->mintAudiences());
        $this->assertSame($this->gzip, (string) $stream);
    }

    public function testDownloadToStream()
    {
        $this->configureMock()->append(self::voiceInToken(), self::priceFile($this->gzip));
        $stream = $this->memory();

        $file = Prices::voiceIn()->download($stream);

        $this->assertSame($this->gzip, (string) $stream, 'the gzipped bytes are written unchanged');
        $this->assertSame(200, $file->getStatusCode());
        $this->assertFalse($file->isNotModified());
        $this->assertSame(self::LAST_MODIFIED_TIMESTAMP, $file->getLastModified()->getTimestamp());
        $this->assertSame('UTC', $file->getLastModified()->getTimezone()->getName());
        $this->assertSame(strlen($this->gzip), $file->getContentLength());
        $this->assertSame('voice_in_prices.csv.gz', $file->getFilename());
        $this->assertSame(strlen($this->gzip), $file->getBytesWritten());

        $request = $this->serviceRequests()[0];
        $this->assertFalse($request->hasHeader('If-Modified-Since'));
        $this->assertFalse($request->hasHeader('If-None-Match'));
        $this->assertFalse($request->hasHeader('Accept'));
        $this->assertFalse($request->hasHeader('Api-Key'));
        $this->assertStringStartsWith('didww-php-sdk/', $request->getHeaderLine('User-Agent'));
        $options = $this->serviceOptions()[0];
        $this->assertFalse($options['decode_content']);
        $this->assertFalse($options['allow_redirects']);
        $this->assertArrayNotHasKey('timeout', $options, 'file requests use the client timeout as given');
    }

    public function testDownloadToResource()
    {
        $this->configureMock()->append(self::voiceInToken(), self::priceFile($this->gzip));
        $resource = fopen('php://memory', 'w+b');

        Prices::voiceIn()->download($resource);

        rewind($resource);
        $this->assertSame($this->gzip, stream_get_contents($resource));
    }

    public function testDownloadSendsIfModifiedSince()
    {
        $this->configureMock()->append(self::voiceInToken(), self::priceFile($this->gzip));

        Prices::voiceIn()->download($this->memory(), new \DateTimeImmutable('2026-10-14T08:00:00+02:00'));

        $this->assertSame(self::LAST_MODIFIED, $this->serviceRequests()[0]->getHeaderLine('If-Modified-Since'));
    }

    public function testDownloadNeverSendsIfNoneMatch()
    {
        $this->configureMock(['headers' => ['If-None-Match' => '"abc"']])->append(self::voiceInToken(), self::priceFile($this->gzip));

        Prices::voiceIn()->download($this->memory());

        $this->assertFalse($this->serviceRequests()[0]->hasHeader('If-None-Match'));
    }

    public function testDownloadNotModified()
    {
        $this->configureMock()->append(self::voiceInToken(), self::notModified());
        $stream = $this->memory();

        $file = Prices::voiceIn()->download($stream, new \DateTimeImmutable('@'.self::LAST_MODIFIED_TIMESTAMP));

        $this->assertTrue($file->isNotModified());
        $this->assertSame(304, $file->getStatusCode());
        $this->assertSame(self::LAST_MODIFIED_TIMESTAMP, $file->getLastModified()->getTimestamp());
        $this->assertNull($file->getContentLength());
        $this->assertSame(0, $file->getBytesWritten());
        $this->assertSame('', (string) $stream);
        $this->assertSame(self::LAST_MODIFIED, $this->serviceRequests()[0]->getHeaderLine('If-Modified-Since'));
    }

    public static function invalidDestinationProvider(): array
    {
        return ['empty path' => [''], 'null' => [null], 'array' => [[]]];
    }

    #[DataProvider('invalidDestinationProvider')]
    public function testDownloadRejectsInvalidDestination($destination)
    {
        $this->configureMock();

        $this->expectException(\InvalidArgumentException::class);
        try {
            Prices::voiceIn()->download($destination);
        } finally {
            $this->assertSame(0, $this->http->count());
        }
    }

    public function testHead()
    {
        $this->configureMock()->append(self::voiceInToken(), self::priceFile(''));

        $file = Prices::voiceIn()->head();

        $request = $this->serviceRequests()[0];
        $this->assertSame('HEAD', $request->getMethod());
        $this->assertSame('/v3/prices.csv.gz', $request->getUri()->getPath());
        $this->assertFalse($request->hasHeader('If-Modified-Since'));
        $this->assertSame(200, $file->getStatusCode());
        $this->assertSame(self::LAST_MODIFIED_TIMESTAMP, $file->getLastModified()->getTimestamp());
        $this->assertSame('voice_in_prices.csv.gz', $file->getFilename());
        $this->assertSame(0, $file->getBytesWritten());
    }

    public function testHeadNotModified()
    {
        $this->configureMock()->append(self::voiceInToken(), self::notModified());

        $file = Prices::voiceIn()->head(new \DateTimeImmutable('@'.self::LAST_MODIFIED_TIMESTAMP));

        $this->assertTrue($file->isNotModified());
        $this->assertSame(self::LAST_MODIFIED, $this->serviceRequests()[0]->getHeaderLine('If-Modified-Since'));
    }

    public function testHeadNotFoundWithoutBody()
    {
        $this->configureMock()->append(self::voiceInToken(), new Response(404));

        $e = $this->expectPricesError(fn () => Prices::voiceIn()->head(), PricesErrorKind::PRICE_PLAN_NOT_ASSIGNED);

        $this->assertSame(404, $e->getStatusCode());
        $this->assertSame('price_plan_not_assigned', $e->getErrorCode());
        $this->assertSame([], $e->getErrors());
    }

    public static function headerProvider(): array
    {
        return [
            'quoted filename' => [['Content-Disposition' => 'attachment; filename="voice_in_prices.csv.gz"'], 'voice_in_prices.csv.gz'],
            'token filename' => [['Content-Disposition' => 'attachment; filename=sms_in_prices.csv.gz'], 'sms_in_prices.csv.gz'],
            'extended filename preferred' => [['Content-Disposition' => "attachment; filename=\"fallback.csv.gz\"; filename*=UTF-8''voice%20in%20prices.csv.gz"], 'voice in prices.csv.gz'],
            'no filename' => [['Content-Disposition' => 'attachment'], null],
            'no Content-Disposition' => [[], null],
        ];
    }

    #[DataProvider('headerProvider')]
    public function testHeadFilename(array $headers, ?string $filename)
    {
        $this->configureMock()->append(self::voiceInToken(), new Response(200, $headers));

        $this->assertSame($filename, Prices::voiceIn()->head()->getFilename());
    }

    public function testHeadIgnoresLastModifiedWithWrongWeekday()
    {
        $this->configureMock()->append(self::voiceInToken(), new Response(200, ['Last-Modified' => 'Mon, 21 Oct 2026 10:00:00 GMT']));

        $this->assertNull(Prices::voiceIn()->head()->getLastModified());
    }

    public function testHeadIgnoresHeadersThatCannotBeParsed()
    {
        $this->configureMock()->append(self::voiceInToken(), new Response(200, [
            'Last-Modified' => 'Wednesday, 14-Oct-26 06:00:00 GMT',
            'Content-Length' => '-1',
        ]));

        $file = Prices::voiceIn()->head();

        $this->assertNull($file->getLastModified());
        $this->assertNull($file->getContentLength());
    }

    public function testHeadRetriesServiceUnavailable()
    {
        $this->configureMock()->append(self::voiceInToken(), new Response(503, ['Retry-After' => '2']), self::priceFile(''));

        $file = $this->voiceIn()->head();

        $this->assertSame(200, $file->getStatusCode());
        $this->assertSame([2.0], $this->sleeps);
        $requests = $this->serviceRequests();
        $this->assertCount(2, $requests);
        $this->assertSame('HEAD', $requests[1]->getMethod());
    }

    public function testDownloadRetriesServiceUnavailable()
    {
        $this->configureMock()->append(self::voiceInToken(), self::servingFailure('1'), self::priceFile($this->gzip));
        $stream = $this->memory();

        $this->voiceIn()->download($stream);

        $this->assertSame($this->gzip, (string) $stream, 'the 503 body is not written');
        $this->assertSame([1.0], $this->sleeps);
        $this->assertCount(2, $this->serviceRequests());
    }

    public function testDownloadServiceUnavailableAfterTwoRetries()
    {
        $this->configureMock()->append(self::voiceInToken(), self::servingFailure('1'), self::servingFailure('1'), self::servingFailure('1'));
        $stream = $this->memory();

        $e = $this->expectPricesError(fn () => $this->voiceIn()->download($stream), PricesErrorKind::SERVICE_UNAVAILABLE);

        $this->assertSame(503, $e->getStatusCode());
        $this->assertSame('serving_failure', $e->getErrorCode());
        $this->assertSame(1, $e->getRetryAfter());
        $this->assertSame('Prices service: The price file is not available yet.', $e->getMessage());
        $this->assertCount(3, $this->serviceRequests());
        $this->assertSame([1.0, 1.0], $this->sleeps);
        $this->assertSame('', (string) $stream);
    }

    public static function spreadProvider(): array
    {
        return [
            'Retry-After 0 without jitter' => ['0', 0.0, 1.0],
            'Retry-After 0 with full jitter' => ['0', 1.0, 2.0],
            'Retry-After 2 without jitter' => ['2', 0.0, 2.0],
            'Retry-After 2 with full jitter' => ['2', 1.0, 3.0],
            'Retry-After 30 without jitter' => ['30', 0.0, 30.0],
            'Retry-After 30 with full jitter' => ['30', 1.0, 36.0],
            'Retry-After 60 with full jitter' => ['60', 1.0, 72.0],
        ];
    }

    #[DataProvider('spreadProvider')]
    public function testServiceUnavailableWaitIsSpread(string $retryAfter, float $jitter, float $wait)
    {
        $this->configureMock()->append(self::voiceInToken(), self::servingFailure($retryAfter), self::priceFile(''));

        $this->voiceIn($jitter)->head();

        $this->assertSame([$wait], $this->sleeps);
    }

    public static function notRetriedProvider(): array
    {
        return [
            'Retry-After over a minute' => ['120', 120],
            'Retry-After as an HTTP date' => ['Wed, 21 Oct 2026 07:28:00 GMT', null],
            'without Retry-After' => [null, null],
        ];
    }

    #[DataProvider('notRetriedProvider')]
    public function testServiceUnavailableNotRetried(?string $retryAfter, ?int $wait)
    {
        $this->configureMock()->append(self::voiceInToken(), self::servingFailure($retryAfter));

        $e = $this->expectPricesError(fn () => $this->voiceIn()->download($this->memory()), PricesErrorKind::SERVICE_UNAVAILABLE);

        $this->assertSame($wait, $e->getRetryAfter());
        $this->assertCount(1, $this->serviceRequests());
        $this->assertSame([], $this->sleeps);
    }

    public function testTooManyRequestsNotRetried()
    {
        $this->configureMock()->append(self::voiceInToken(), new Response(429, ['Retry-After' => '30']));

        $e = $this->expectPricesError(fn () => $this->voiceIn()->download($this->memory()), PricesErrorKind::TOO_MANY_REQUESTS);

        $this->assertSame(429, $e->getStatusCode());
        $this->assertSame(30, $e->getRetryAfter());
        $this->assertCount(1, $this->serviceRequests());
        $this->assertSame([], $this->sleeps);
    }

    public function testDownloadRetriesRefusedTokenOnce()
    {
        $this->configureMock()->append(self::voiceInToken('token-1'), self::unauthorizedPrices(), self::voiceInToken('token-2'), self::priceFile($this->gzip));
        $stream = $this->memory();

        Prices::voiceIn()->download($stream);

        $this->assertSame($this->gzip, (string) $stream, 'only the file bytes are written');
        $requests = $this->serviceRequests();
        $this->assertCount(2, $requests);
        $this->assertSame('Bearer token-2', $requests[1]->getHeaderLine('Authorization'));
        $this->assertSame([Audience::VOICE_IN, Audience::VOICE_IN], $this->mintAudiences());
    }

    public function testClientCallbacksRunForEveryTransfer()
    {
        $headers = [];
        $stats = [];
        $this->configureMock([
            'on_headers' => function (ResponseInterface $response, RequestInterface $request) use (&$headers): void {
                $headers[] = $response->getStatusCode().' '.$request->getUri()->getPath();
            },
            'on_stats' => function (TransferStats $transfer) use (&$stats): void {
                if ('/token' !== $transfer->getRequest()->getUri()->getPath()) {
                    $stats[] = $transfer->getResponse()?->getStatusCode();
                }
            },
        ])->append(self::voiceInToken('token-1'), self::unauthorizedPrices(), self::voiceInToken('token-2'), self::priceFile($this->gzip));
        $stream = $this->memory();

        Prices::voiceIn()->download($stream);

        $this->assertSame($this->gzip, (string) $stream);
        $this->assertSame(['200 /token', '401 /v3/prices.csv.gz', '200 /token', '200 /v3/prices.csv.gz'], $headers);
        $this->assertSame([401, 200], $stats);
    }

    public function testDownloadToPathOfWrapperWithoutMetadata()
    {
        NoMetadataStreamWrapper::register();
        try {
            $this->configureMock()->append(self::voiceInToken(), self::priceFile($this->gzip));

            $file = Prices::voiceIn()->download('nometa://bucket/voice_in_prices.csv.gz');

            $this->assertSame(200, $file->getStatusCode());
            $this->assertSame(['nometa://bucket/voice_in_prices.csv.gz' => $this->gzip], NoMetadataStreamWrapper::$files);
        } finally {
            NoMetadataStreamWrapper::unregister();
        }
    }

    public function testFailedRenameOnWrapperWithoutMetadataIsReported()
    {
        NoMetadataStreamWrapper::register();
        NoMetadataStreamWrapper::$failRename = true;
        try {
            $this->configureMock()->append(self::voiceInToken(), self::priceFile($this->gzip));

            try {
                Prices::voiceIn()->download('nometa://bucket/voice_in_prices.csv.gz');
                $this->fail('No exception');
            } catch (\RuntimeException $e) {
                $this->assertStringStartsWith('Failed to rename ', $e->getMessage());
            }
            $this->assertSame([], NoMetadataStreamWrapper::$files);
        } finally {
            NoMetadataStreamWrapper::unregister();
        }
    }

    public function testDownloadToPathRetriesRefusedTokenOnce()
    {
        $this->configureMock()->append(self::voiceInToken('token-1'), self::unauthorizedPrices(), self::voiceInToken('token-2'), self::priceFile($this->gzip));

        Prices::voiceIn()->download($this->path());

        $this->assertSame($this->gzip, file_get_contents($this->path()));
    }

    public function testDownloadToPathNotAuthorized()
    {
        $this->configureMock()->append(self::voiceInToken('token-1'), self::unauthorizedPrices(), self::voiceInToken('token-2'), self::unauthorizedPrices());

        $e = $this->expectPricesError(fn () => Prices::voiceIn()->download($this->path()), PricesErrorKind::NOT_AUTHORIZED);

        $this->assertSame(401, $e->getStatusCode());
        $this->assertSame('401', $e->getErrorCode());
        $this->assertSame('Prices service: Authorization failed', $e->getMessage());
        $this->assertSame([], $this->dirEntries());
    }

    public function testDownloadPricePlanNotAssigned()
    {
        $this->configureMock()->append(self::voiceInToken(), self::pricesJson(404, self::fixture('errors/price_plan_not_assigned.json')));
        $stream = $this->memory();

        $e = $this->expectPricesError(fn () => Prices::voiceIn()->download($stream), PricesErrorKind::PRICE_PLAN_NOT_ASSIGNED);

        $this->assertSame('Prices service: No price plan is assigned to this account.', $e->getMessage());
        $this->assertSame('', (string) $stream);
    }

    public function testDownloadAccessDenied()
    {
        $this->configureMock()->append(self::voiceInToken(), self::pricesJson(403, '{"errors":[{"title":"Insufficient scope","code":"403","status":"403"}]}'));

        $e = $this->expectPricesError(fn () => Prices::voiceIn()->download($this->memory()), PricesErrorKind::ACCESS_DENIED);

        $this->assertSame('Prices service: Insufficient scope', $e->getMessage());
        $this->assertCount(1, $this->serviceRequests());
        $this->assertCount(1, $this->http->requestsTo('/token'));
    }

    public static function unexpectedStatusProvider(): array
    {
        return [['202', 202], ['204', 204], ['302', 302]];
    }

    #[DataProvider('unexpectedStatusProvider')]
    public function testDownloadUnexpectedStatus(string $name, int $status)
    {
        $this->configureMock()->append(self::voiceInToken(), new Response($status, ['Location' => '/v3/elsewhere.csv.gz']));

        $e = $this->expectPricesError(fn () => Prices::voiceIn()->download($this->path()), PricesErrorKind::UNEXPECTED_STATUS);

        $this->assertSame($status, $e->getStatusCode());
        $this->assertSame('Prices service responded with status '.$name, $e->getMessage());
        $this->assertCount(1, $this->serviceRequests(), 'redirects are not followed');
        $this->assertSame([], $this->dirEntries());
    }

    public function testErrorBodyIsBounded()
    {
        $body = '{"errors":[{"detail":"'.str_repeat('x', 2 << 20).'"}]}';
        $this->configureMock()->append(self::voiceInToken(), self::pricesJson(404, $body));
        $stream = $this->memory();

        $e = $this->expectPricesError(fn () => Prices::voiceIn()->download($stream), PricesErrorKind::PRICE_PLAN_NOT_ASSIGNED);

        $this->assertSame('Prices service responded with status 404', $e->getMessage(), 'the body is cut at 1 MiB');
        $this->assertSame('', (string) $stream, 'an error body never reaches the destination');
    }

    public function testDestinationStreamErrorIsThrownAsIs()
    {
        $this->configureMock()->append(self::voiceInToken(), self::priceFile($this->gzip));
        $failure = new \RuntimeException('disk full');
        $stream = new class(fopen('php://memory', 'w+b'), $failure) extends Stream {
            public function __construct($resource, private \RuntimeException $failure)
            {
                parent::__construct($resource);
            }

            public function write($string): int
            {
                throw $this->failure;
            }
        };

        try {
            Prices::voiceIn()->download($stream);
            $this->fail('expected an exception');
        } catch (\RuntimeException $e) {
            $this->assertSame($failure, $e);
        }
    }

    public function testReadOnlyResourceFails()
    {
        file_put_contents($this->path('read_only'), 'x');
        $this->configureMock()->append(self::voiceInToken('secret-token'), self::priceFile($this->gzip));
        $resource = fopen($this->path('read_only'), 'rb');

        try {
            TraceArgs::keepingArgs(fn () => Prices::voiceIn()->download($resource));
            $this->fail('expected an exception');
        } catch (\RuntimeException $e) {
            $this->assertNotInstanceOf(DidwwPricesException::class, $e);
            $this->assertStringStartsWith('Failed to write the price file', $e->getMessage());
            $this->assertFalse(str_contains(TraceArgs::ofSdkFrames($e), 'secret-token'));
            $this->assertFalse(str_contains(TraceArgs::ofSdkFrames($e, 'GuzzleHttp\\'), 'secret-token'));
        } finally {
            fclose($resource);
        }
    }

    public function testConnectionErrorKeepsExistingFile()
    {
        file_put_contents($this->path(), 'old copy');
        $failure = new ConnectException('Connection refused', new Request('GET', 'https://api-vin-sandbox.didww.com/v3/prices.csv.gz'));
        $this->configureMock()->append(self::voiceInToken(), $failure);

        $e = $this->expectPricesError(fn () => Prices::voiceIn()->download($this->path()), PricesErrorKind::CONNECTION_ERROR);

        $this->assertNull($e->getStatusCode());
        $this->assertSame($failure, $e->getPrevious());
        $this->assertSame('old copy', file_get_contents($this->path()));
        $this->assertSame(['voice_in_prices.csv.gz'], $this->dirEntries());
    }

    public function testIncompleteBodyKeepsExistingFile()
    {
        // A handler that does not check the body against its Content-Length.
        file_put_contents($this->path(), 'old copy');
        $this->configureMock()->append(self::voiceInToken(), new Response(200, ['Content-Type' => 'application/gzip', 'Content-Length' => '1000'], '0123456789'));

        $e = $this->expectPricesError(fn () => Prices::voiceIn()->download($this->path()), PricesErrorKind::CONNECTION_ERROR);

        $this->assertStringContainsString('incomplete body: 10 of 1000 bytes', $e->getMessage());
        $this->assertSame(200, $e->getStatusCode());
        $this->assertSame('old copy', file_get_contents($this->path()));
        $this->assertSame(['voice_in_prices.csv.gz'], $this->dirEntries());
    }

    public function testDownloadToPath()
    {
        file_put_contents($this->path(), 'old copy');
        chmod($this->path(), 0600);
        $this->configureMock()->append(self::voiceInToken(), self::priceFile($this->gzip));

        $file = Prices::voiceIn()->download($this->path());

        clearstatcache();
        $this->assertSame($this->gzip, file_get_contents($this->path()));
        $this->assertSame(self::LAST_MODIFIED_TIMESTAMP, filemtime($this->path()), 'modification time is Last-Modified');
        $this->assertSame(['voice_in_prices.csv.gz'], $this->dirEntries());
        $this->assertSame(strlen($this->gzip), $file->getBytesWritten());
        $this->assertSame('voice_in_prices.csv.gz', $file->getFilename());
        if ('Windows' !== PHP_OS_FAMILY) {
            $this->assertSame(0666 & ~umask(), fileperms($this->path()) & 0777, 'mode of a new file, not of the old copy');
        }
    }

    public function testDownloadToPathLeavesOtherTempFiles()
    {
        file_put_contents($this->path().'.tmp', 'concurrent download');
        $this->configureMock()->append(self::voiceInToken(), self::priceFile($this->gzip));

        Prices::voiceIn()->download($this->path());

        $this->assertSame($this->gzip, file_get_contents($this->path()));
        $this->assertSame('concurrent download', file_get_contents($this->path().'.tmp'));
        $this->assertSame(['voice_in_prices.csv.gz', 'voice_in_prices.csv.gz.tmp'], $this->dirEntries());
    }

    public function testDownloadToPathErrorKeepsExistingFile()
    {
        file_put_contents($this->path(), 'old copy');
        $this->configureMock()->append(self::voiceInToken(), self::pricesJson(404, self::fixture('errors/price_plan_not_assigned.json')));

        $this->expectPricesError(fn () => Prices::voiceIn()->download($this->path()), PricesErrorKind::PRICE_PLAN_NOT_ASSIGNED);

        $this->assertSame('old copy', file_get_contents($this->path()));
        $this->assertSame(['voice_in_prices.csv.gz'], $this->dirEntries());
    }

    public function testDownloadToPathInMissingDirectory()
    {
        $this->configureMock()->append(self::voiceInToken(), self::priceFile($this->gzip));

        try {
            Prices::voiceIn()->download($this->dir.'/missing/prices.csv.gz');
            $this->fail('expected an exception');
        } catch (\RuntimeException $e) {
            $this->assertNotInstanceOf(DidwwPricesException::class, $e);
        }
        $this->assertSame(0, $this->http->count());
    }

    public function testDownloadToPathFollowsUmask()
    {
        if ('Windows' === PHP_OS_FAMILY) {
            $this->markTestSkipped('POSIX file modes');
        }
        file_put_contents($this->path('voice_out_prices.csv.gz'), 'old copy');
        chmod($this->path('voice_out_prices.csv.gz'), 0600);
        $this->configureMock()->append(self::voiceInToken(), self::priceFile($this->gzip), self::priceFile($this->gzip));
        $umask = umask(0027);
        try {
            Prices::voiceIn()->download($this->path());
            Prices::voiceIn()->download($this->path('voice_out_prices.csv.gz'));
        } finally {
            umask($umask);
        }

        clearstatcache();
        $this->assertSame(0640, fileperms($this->path()) & 0777);
        $this->assertSame(0640, fileperms($this->path('voice_out_prices.csv.gz')) & 0777);
    }

    public function testFetchWithoutCopy()
    {
        $this->configureMock()->append(self::voiceInToken(), self::priceFile($this->gzip));

        $file = Prices::voiceIn()->fetch($this->path());

        $this->assertSame(200, $file->getStatusCode());
        $this->assertSame($this->gzip, file_get_contents($this->path()));
        $this->assertFalse($this->serviceRequests()[0]->hasHeader('If-Modified-Since'));
    }

    public function testFetchSendsModificationTime()
    {
        file_put_contents($this->path(), 'previous copy');
        touch($this->path(), self::LAST_MODIFIED_TIMESTAMP);
        $this->configureMock()->append(self::voiceInToken(), self::notModified());

        $file = Prices::voiceIn()->fetch($this->path());

        $this->assertTrue($file->isNotModified());
        $this->assertSame(self::LAST_MODIFIED, $this->serviceRequests()[0]->getHeaderLine('If-Modified-Since'));
        $this->assertSame('previous copy', file_get_contents($this->path()));
        $this->assertSame(['voice_in_prices.csv.gz'], $this->dirEntries());
    }

    public function testFetchReplacesChangedCopy()
    {
        $this->configureMock()->append(
            self::voiceInToken(),
            self::priceFile('previous copy'),
            self::priceFile($this->gzip, self::OTHER_LAST_MODIFIED),
            self::notModified(self::OTHER_LAST_MODIFIED),
        );
        $prices = Prices::voiceIn();

        for ($i = 0; $i < 3; ++$i) {
            $prices->fetch($this->path());
        }

        $requests = $this->serviceRequests();
        $this->assertCount(3, $requests);
        $this->assertFalse($requests[0]->hasHeader('If-Modified-Since'));
        $this->assertSame(self::LAST_MODIFIED, $requests[1]->getHeaderLine('If-Modified-Since'));
        $this->assertSame(self::OTHER_LAST_MODIFIED, $requests[2]->getHeaderLine('If-Modified-Since'));
        foreach ($requests as $request) {
            $this->assertFalse($request->hasHeader('If-None-Match'));
        }
        clearstatcache();
        $this->assertSame($this->gzip, file_get_contents($this->path()));
        $this->assertSame(self::OTHER_LAST_MODIFIED_TIMESTAMP, filemtime($this->path()));
        $this->assertSame(['voice_in_prices.csv.gz'], $this->dirEntries());
    }

    public function testFetchRejectsEmptyPath()
    {
        $this->configureMock();

        $this->expectException(\InvalidArgumentException::class);
        Prices::voiceIn()->fetch('');
    }
}
