<?php

namespace Didww\Tests;

use Didww\Configuration;
use Didww\Credentials;
use Didww\Item\Export;
use Didww\Tests\Prices\Support\ScriptedServer;
use Didww\Tests\Support\FixedTokenStore;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use VCR\VCR;

/**
 * Export downloads over real curl against a scripted local server.
 */
#[Group('wire')]
#[RequiresPhpExtension('curl')]
class ExportWireTest extends TestCase
{
    private const EXPORT_ID = '9df99644-f1a5-4a3c-99a4-559d758eb96b';

    private ?ScriptedServer $server = null;
    private string $dir;

    protected function setUp(): void
    {
        VCR::turnOff();
        $this->dir = sys_get_temp_dir().'/didww_export_wire_'.bin2hex(random_bytes(6));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        $this->server?->stop();
        foreach (glob($this->dir.'/*') as $file) {
            unlink($file);
        }
        rmdir($this->dir);
        VCR::turnOn();
    }

    public function testLargeErrorBodyIsCut()
    {
        // Valid JSON whose detail would show if the body were read in full,
        // followed by a stall that only an abort at 1 MiB does not wait for.
        $body = '{"errors":[{"detail":"'.str_repeat('x', 2 << 20).'"}]}';
        $this->server = new ScriptedServer([[
            ['send' => "HTTP/1.1 404 Not Found\r\nContent-Type: application/vnd.api+json\r\nContent-Length: ".(strlen($body) + 1)."\r\n\r\n".$body],
            ['sleep' => 10],
        ]]);
        Configuration::configure(new Credentials('test-api-key', 'sandbox'), tokenStore: new FixedTokenStore(), baseUrl: $this->server->url);
        $export = Export::build(self::EXPORT_ID, ['url' => $this->server->url.'/v3/exports/'.self::EXPORT_ID.'.csv.gz']);
        $started = microtime(true);

        $result = $export->download($this->dir.'/export.csv.gz');

        $this->assertSame('Export download failed with status 404', $result);
        $this->assertLessThan(5, microtime(true) - $started);
        $this->assertSame([], glob($this->dir.'/*'));
    }

    public function testStalledErrorBodyKeepsTheStatus()
    {
        // curl ends the stalled body with a timeout (errno 28).
        $this->server = new ScriptedServer([[
            ['send' => "HTTP/1.1 503 Service Unavailable\r\nContent-Type: application/vnd.api+json\r\nContent-Length: 100\r\n\r\n{\"errors\""],
            ['sleep' => 10],
        ]]);
        Configuration::configure(new Credentials('test-api-key', 'sandbox'), ['timeout' => 1], tokenStore: new FixedTokenStore(), baseUrl: $this->server->url);
        $export = Export::build(self::EXPORT_ID, ['url' => $this->server->url.'/v3/exports/'.self::EXPORT_ID.'.csv.gz']);

        $result = $export->download($this->dir.'/export.csv.gz');

        $this->assertSame('Export download failed with status 503', $result);
        $this->assertSame([], glob($this->dir.'/*'));
    }

    public function testTruncatedBodyWithStreamHandlerKeepsTheDestination()
    {
        $gzip = gzencode("id,number\n1,18005550123\n");
        $this->server = new ScriptedServer([[['send' => "HTTP/1.1 200 OK\r\nContent-Type: application/gzip\r\nContent-Length: ".strlen($gzip)."\r\nConnection: close\r\n\r\n".substr($gzip, 0, 10)]]]);
        Configuration::configure(new Credentials('test-api-key', 'sandbox'), ['handler' => HandlerStack::create(new StreamHandler())], tokenStore: new FixedTokenStore(), baseUrl: $this->server->url);
        $export = Export::build(self::EXPORT_ID, ['url' => $this->server->url.'/v3/exports/'.self::EXPORT_ID.'.csv.gz']);
        $dest = $this->dir.'/export.csv.gz';
        file_put_contents($dest, 'old export');

        $result = $export->download($dest);

        // Guzzle's stream handler reports the body cut short.
        $this->assertStringStartsWith('Export download failed: ', $result);
        $this->assertStringNotContainsString('status', $result);
        $this->assertSame('old export', file_get_contents($dest));
        $this->assertSame([$dest], glob($this->dir.'/*'));
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
        $gzip = gzencode("id,number\n1,18005550123\n");
        $this->server = new ScriptedServer([[['send' => "HTTP/1.1 200 OK\r\nContent-Type: application/gzip\r\nContent-Length: ".strlen($gzip)."\r\nConnection: close\r\n\r\n".$gzip]]]);
        Configuration::configure(new Credentials('test-api-key', 'sandbox'), $httpClientConfig(), tokenStore: new FixedTokenStore(), baseUrl: $this->server->url);
        $export = Export::build(self::EXPORT_ID, ['url' => $this->server->url.'/v3/exports/'.self::EXPORT_ID.'.csv.gz']);

        $result = $export->downloadAndDecompress($this->dir.'/export.csv');

        $this->assertTrue($result);
        $this->assertSame("id,number\n1,18005550123\n", file_get_contents($this->dir.'/export.csv'));
    }
}
