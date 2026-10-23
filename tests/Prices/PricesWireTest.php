<?php

namespace Didww\Tests\Prices;

use Didww\Configuration;
use Didww\Credentials;
use Didww\Exception\DidwwPricesException;
use Didww\Exception\PricesErrorKind;
use Didww\Prices;
use Didww\Tests\Prices\Support\ScriptedServer;
use Didww\Tests\Support\FixedTokenStore;
use GuzzleHttp\Exception\ResponseTransferException;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use VCR\VCR;

/**
 * Price file requests over real curl against a scripted local server.
 */
#[Group('wire')]
#[RequiresPhpExtension('curl')]
class PricesWireTest extends TestCase
{
    private const LAST_MODIFIED = 'Wed, 14 Oct 2026 06:00:00 GMT';

    private ?ScriptedServer $server = null;
    private string $dir;

    protected function setUp(): void
    {
        VCR::turnOff();
        $this->dir = sys_get_temp_dir().'/didww_prices_wire_'.bin2hex(random_bytes(6));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        $this->server?->stop();
        foreach (array_diff(scandir($this->dir), ['.', '..']) as $entry) {
            unlink($this->dir.'/'.$entry);
        }
        rmdir($this->dir);
        VCR::turnOn();
    }

    /**
     * @param list<list<array{send?: string, sleep?: float}>> $replies
     */
    private function serve(array $replies, array $httpClientConfig = []): void
    {
        $this->server = new ScriptedServer($replies);
        Configuration::configure(new Credentials('test-api-key', 'sandbox'), $httpClientConfig, tokenStore: new FixedTokenStore(), voiceInPricesUrl: $this->server->url);
    }

    private function path(): string
    {
        return $this->dir.'/voice_in_prices.csv.gz';
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

    public function testWireHeadersAndUnchangedBytes()
    {
        $gzip = gzencode("Country,Rate\nUS,0.1\n");
        $this->serve([[['send' => "HTTP/1.1 200 OK\r\nContent-Type: application/gzip\r\nContent-Length: ".strlen($gzip)."\r\nLast-Modified: ".self::LAST_MODIFIED."\r\n\r\n".$gzip]]], [
            'headers' => ['If-None-Match' => '"abc"'],
        ]);

        $file = Prices::voiceIn()->download($this->path(), new \DateTimeImmutable('2026-10-14T08:00:00+02:00'));

        $this->assertSame($gzip, file_get_contents($this->path()));
        $this->assertSame(strlen($gzip), $file->getBytesWritten());
        $head = $this->server->requests()[0];
        $this->assertStringStartsWith("GET /v3/prices.csv.gz HTTP/1.1\r\n", $head);
        $this->assertStringContainsString("\r\nIf-Modified-Since: ".self::LAST_MODIFIED."\r\n", $head);
        $this->assertStringContainsString("\r\nAuthorization: Bearer ".FixedTokenStore::ACCESS_TOKEN."\r\n", $head);
        $this->assertStringNotContainsStringIgnoringCase('Accept-Encoding:', $head);
        $this->assertStringNotContainsStringIgnoringCase("\r\nAccept:", $head);
        $this->assertStringNotContainsStringIgnoringCase('If-None-Match:', $head);
        $this->assertStringNotContainsStringIgnoringCase('Api-Key:', $head);
    }

    public function testTruncatedBodyKeepsExistingFile()
    {
        file_put_contents($this->path(), 'old copy');
        $this->serve([[['send' => "HTTP/1.1 200 OK\r\nContent-Type: application/gzip\r\nContent-Length: 1000\r\n\r\n0123456789"]]]);

        $e = $this->expectPricesError(fn () => Prices::voiceIn()->download($this->path()), PricesErrorKind::CONNECTION_ERROR);

        $this->assertSame(200, $e->getStatusCode());
        $this->assertSame('old copy', file_get_contents($this->path()));
        $this->assertSame(['voice_in_prices.csv.gz'], array_values(array_diff(scandir($this->dir), ['.', '..'])));
    }

    public function testTruncatedBodyWithStreamHandlerKeepsExistingFile()
    {
        file_put_contents($this->path(), 'old copy');
        $this->serve(
            [[['send' => "HTTP/1.1 200 OK\r\nContent-Type: application/gzip\r\nContent-Length: 1000\r\nLast-Modified: ".self::LAST_MODIFIED."\r\nConnection: close\r\n\r\n0123456789"]]],
            ['handler' => HandlerStack::create(new StreamHandler())],
        );

        $e = $this->expectPricesError(fn () => Prices::voiceIn()->download($this->path()), PricesErrorKind::CONNECTION_ERROR);

        // Guzzle's stream handler reports the body cut short.
        $this->assertInstanceOf(ResponseTransferException::class, $e->getPrevious());
        $this->assertSame(200, $e->getStatusCode());
        $this->assertSame('old copy', file_get_contents($this->path()));
        $this->assertSame(['voice_in_prices.csv.gz'], array_values(array_diff(scandir($this->dir), ['.', '..'])));
    }

    public function testTruncatedLookupBodyWithStreamHandler()
    {
        $this->serve(
            [[['send' => "HTTP/1.1 200 OK\r\nContent-Type: application/vnd.api+json\r\nContent-Length: 1000\r\nConnection: close\r\n\r\n{\"data\":["]]],
            ['handler' => HandlerStack::create(new StreamHandler())],
        );

        $e = $this->expectPricesError(fn () => Prices::voiceIn()->lookup(['number' => '18005550123']), PricesErrorKind::CONNECTION_ERROR);

        $this->assertInstanceOf(ResponseTransferException::class, $e->getPrevious());
        $this->assertSame(200, $e->getStatusCode());
    }

    public function testStallBeforeHeadersTimesOut()
    {
        $this->serve([[['sleep' => 5]]], ['curl' => [CURLOPT_LOW_SPEED_TIME => 1]]);
        $started = microtime(true);

        $e = $this->expectPricesError(fn () => Prices::voiceIn()->head(), PricesErrorKind::CONNECTION_ERROR);

        $this->assertNull($e->getStatusCode());
        $this->assertLessThan(4, microtime(true) - $started);
    }

    public function testRedirectNotFollowed()
    {
        $this->serve([
            [['send' => "HTTP/1.1 302 Found\r\nLocation: /v3/elsewhere.csv.gz\r\nContent-Length: 0\r\n\r\n"]],
            [['send' => "HTTP/1.1 200 OK\r\nContent-Length: 0\r\n\r\n"]],
        ]);

        $e = $this->expectPricesError(fn () => Prices::voiceIn()->download($this->path()), PricesErrorKind::UNEXPECTED_STATUS);

        $this->assertSame(302, $e->getStatusCode());
        $this->assertCount(1, $this->server->requests());
        $this->assertFileDoesNotExist($this->path());
    }

    public function testLargeErrorBodyIsCut()
    {
        // Valid JSON whose detail would show if the body were read in full,
        // followed by a stall that only an abort at 1 MiB does not wait for.
        $body = '{"errors":[{"detail":"'.str_repeat('x', 2 << 20).'"}]}';
        $this->serve([[
            ['send' => "HTTP/1.1 404 Not Found\r\nContent-Type: application/vnd.api+json\r\nContent-Length: ".(strlen($body) + 1)."\r\n\r\n".$body],
            ['sleep' => 10],
        ]]);
        $started = microtime(true);

        $e = $this->expectPricesError(fn () => Prices::voiceIn()->download($this->path()), PricesErrorKind::PRICE_PLAN_NOT_ASSIGNED);

        $this->assertSame(404, $e->getStatusCode());
        $this->assertSame('Prices service responded with status 404', $e->getMessage());
        $this->assertLessThan(5, microtime(true) - $started);
        $this->assertFileDoesNotExist($this->path());
    }

    public function testStalledErrorBodyKeepsTheStatusAndIsRetried()
    {
        $stalled = [['send' => "HTTP/1.1 503 Service Unavailable\r\nRetry-After: 1\r\nContent-Length: 100\r\n\r\n{\"errors\":[]"], ['sleep' => 10]];
        // curl ends the stalled body with a timeout (errno 28), the error of the
        // stall limit too.
        $this->serve([$stalled, $stalled, $stalled], ['timeout' => 1]);
        $service = Prices::voiceIn()->withRetryTiming(static function (float $seconds): void {}, static fn (): float => 0.0);

        $e = $this->expectPricesError(fn () => $service->download($this->path()), PricesErrorKind::SERVICE_UNAVAILABLE);

        $this->assertSame(503, $e->getStatusCode());
        $this->assertSame(1, $e->getRetryAfter());
        $this->assertCount(3, $this->server->requests());
        $this->assertFileDoesNotExist($this->path());
    }

    public function testStalledBodyKeepsTheStatus()
    {
        $this->serve([[['send' => "HTTP/1.1 200 OK\r\nContent-Type: application/gzip\r\nContent-Length: 1000\r\n\r\n0123456789"], ['sleep' => 10]]], ['timeout' => 1]);

        $e = $this->expectPricesError(fn () => Prices::voiceIn()->download($this->path()), PricesErrorKind::CONNECTION_ERROR);

        $this->assertSame(200, $e->getStatusCode());
        $this->assertFileDoesNotExist($this->path());
    }

    public function testConnectionRefused()
    {
        $this->serve([]);
        $url = $this->server->url;
        $this->server->stop();
        Configuration::configure(new Credentials('test-api-key', 'sandbox'), [], tokenStore: new FixedTokenStore(), voiceInPricesUrl: $url);

        $e = $this->expectPricesError(fn () => Prices::voiceIn()->lookup(['number' => '18005550123']), PricesErrorKind::CONNECTION_ERROR);

        $this->assertNull($e->getStatusCode());
    }

    public static function streamingClientProvider(): array
    {
        return [
            'stream handler' => [static fn () => ['handler' => HandlerStack::create(new StreamHandler())]],
            'stream option' => [static fn () => ['stream' => true]],
        ];
    }

    #[DataProvider('streamingClientProvider')]
    public function testDownloadWithStreamingClient(callable $httpClientConfig)
    {
        $gzip = gzencode("Country,Rate\nUS,0.1\n");
        $this->serve([[['send' => "HTTP/1.1 200 OK\r\nContent-Type: application/gzip\r\nContent-Length: ".strlen($gzip)."\r\nLast-Modified: ".self::LAST_MODIFIED."\r\nConnection: close\r\n\r\n".$gzip]]], $httpClientConfig());

        $file = Prices::voiceIn()->download($this->path());

        $this->assertSame($gzip, file_get_contents($this->path()));
        $this->assertSame(strlen($gzip), $file->getBytesWritten());
    }
}
