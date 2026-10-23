<?php

namespace Didww\Tests;

use Didww\Auth\TokenProvider;
use Didww\Credentials;
use Didww\Exception\DidwwAuthException;
use Didww\Item\Export;
use Didww\Tests\Support\ConfiguresMockHttp;
use Didww\Tests\Support\MockHttp;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ExportDownloadTest extends TestCase
{
    use ConfiguresMockHttp;

    private const EXPORT_ID = '9df99644-f1a5-4a3c-99a4-559d758eb96b';
    private const URL = 'https://sandbox-api.didww.com/v3/exports/9df99644-f1a5-4a3c-99a4-559d758eb96b.csv.gz';

    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/didww_export_test_'.bin2hex(random_bytes(6));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        TokenProvider::forgetRateLimits();
        foreach (glob($this->dir.'/*') as $file) {
            unlink($file);
        }
        rmdir($this->dir);
    }

    public function testDownloadWritesTheFileUnchanged()
    {
        $gzip = gzencode("Date/Time Start (UTC),Source\n2026-10-01 10:00:00,15551234567\n");
        $this->configureMock()->append(MockHttp::token('token-1'), new Response(200, ['Content-Type' => 'application/gzip'], $gzip));
        $dest = $this->dir.'/export.csv.gz';

        $this->assertTrue($this->export()->download($dest));

        $this->assertSame($gzip, file_get_contents($dest));
        $this->assertSame([$dest], glob($this->dir.'/*'), 'no temporary file is left behind');
        $request = $this->http->request(1);
        $this->assertSame(self::URL, (string) $request->getUri());
        $this->assertSame('Bearer token-1', $request->getHeaderLine('Authorization'));
        $this->assertFalse($request->hasHeader('Api-Key'));
        $this->assertFalse($request->hasHeader('X-DIDWW-API-Version'));
        $this->assertFalse($this->http->options(1)['decode_content']);
        $this->assertFalse($this->http->options(1)['allow_redirects']);
    }

    public function testDownloadToStream()
    {
        $this->configureMock()->append(MockHttp::token(), new Response(200, [], 'gzip-bytes'));
        $stream = fopen('php://memory', 'w+b');

        $this->assertTrue($this->export()->download($stream));

        rewind($stream);
        $this->assertSame('gzip-bytes', stream_get_contents($stream));
    }

    public function testDownloadAndDecompress()
    {
        $this->configureMock()->append(MockHttp::token(), new Response(200, [], gzencode("a,b\n1,2\n")));
        $dest = $this->dir.'/export.csv';

        $this->assertTrue($this->export()->downloadAndDecompress($dest));

        $this->assertSame("a,b\n1,2\n", file_get_contents($dest));
    }

    public static function failureProvider(): array
    {
        return [
            'redirect' => [new Response(302, ['Location' => 'https://elsewhere.example.com/export.csv.gz']), 'Export download failed with status 302'],
            'no content' => [new Response(204), 'Export download failed with status 204'],
            'not found' => [new Response(404, [], 'Not Found'), 'Export download failed with status 404'],
            'JSON:API error' => [new Response(422, ['Content-Type' => 'application/vnd.api+json'], '{"errors":[{"title":"Invalid","detail":"export is not completed"},{"title":"Expired"}]}'), 'Export download failed with status 422: export is not completed; Expired'],
            'server error' => [new Response(500), 'Export download failed with status 500'],
        ];
    }

    #[DataProvider('failureProvider')]
    public function testFailureKeepsTheDestination(Response $response, string $message)
    {
        $this->configureMock()->append(MockHttp::token(), $response, new Response(200, [], 'redirect target'));
        $dest = $this->dir.'/export.csv.gz';
        file_put_contents($dest, 'previous');

        $this->assertSame($message, $this->export()->download($dest));

        $this->assertSame('previous', file_get_contents($dest));
        $this->assertSame([$dest], glob($this->dir.'/*'));
        $this->assertSame(2, $this->http->count(), 'a redirect is not followed');
    }

    public function testIncompleteBodyKeepsTheDestination()
    {
        // A handler that does not check the body against its Content-Length.
        $this->configureMock()->append(MockHttp::token(), new Response(200, ['Content-Type' => 'application/gzip', 'Content-Length' => '1000'], '0123456789'));
        $dest = $this->dir.'/export.csv.gz';
        file_put_contents($dest, 'previous');

        $this->assertSame('Export download failed: incomplete body', $this->export()->download($dest));

        $this->assertSame('previous', file_get_contents($dest));
        $this->assertSame([$dest], glob($this->dir.'/*'));
    }

    public function testTransportFailureReturnsMessage()
    {
        $this->configureMock()->append(MockHttp::token(), new ConnectException('cURL error 28: Operation timed out', new Request('GET', self::URL)));
        $dest = $this->dir.'/export.csv.gz';

        $this->assertSame('Export download failed: cURL error 28: Operation timed out', $this->export()->download($dest));
        $this->assertSame([], glob($this->dir.'/*'));
    }

    public function testMissingDirectoryReturnsMessage()
    {
        $this->configureMock();

        $this->assertSame('Failed to open destination file for writing', $this->export()->download($this->dir.'/missing/export.csv.gz'));
        $this->assertSame(0, $this->http->count());
    }

    public function testRetriedAfter401()
    {
        $this->configureMock()->append(MockHttp::token('token-1'), self::unauthorized(), MockHttp::token('token-2'), new Response(200, [], 'gzip-bytes'));
        $dest = $this->dir.'/export.csv.gz';

        $this->assertTrue($this->export()->download($dest));

        $this->assertSame('gzip-bytes', file_get_contents($dest));
        $this->assertSame('Bearer token-2', $this->http->request(3)->getHeaderLine('Authorization'));
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
        ])->append(MockHttp::token('token-1'), self::unauthorized(), MockHttp::token('token-2'), new Response(200, [], 'gzip-bytes'));
        $dest = $this->dir.'/export.csv.gz';

        $this->assertTrue($this->export()->download($dest));

        $this->assertSame('gzip-bytes', file_get_contents($dest));
        $exportPath = '/v3/exports/'.self::EXPORT_ID.'.csv.gz';
        $this->assertSame(['200 /token', '401 '.$exportPath, '200 /token', '200 '.$exportPath], $headers);
        $this->assertSame([401, 200], $stats);
    }

    public function testAuthFailureThrows()
    {
        $this->configureMock()->append(MockHttp::tokenError(401, 'invalid_client.json'));
        $dest = $this->dir.'/export.csv.gz';

        try {
            $this->export()->download($dest);
            $this->fail('Expected a DidwwAuthException');
        } catch (DidwwAuthException) {
        }
        $this->assertSame([], glob($this->dir.'/*'));
    }

    public static function originProvider(): array
    {
        $proxy = 'https://proxy.example.com/v3';

        return [
            'base URL host' => [$proxy, 'https://proxy.example.com/v3/exports/1.csv.gz', true],
            'environment host through a proxy base URL' => [$proxy, 'https://api.didww.com/v3/exports/1.csv.gz', true],
            'environment host with the default port' => [$proxy, 'https://api.didww.com:443/v3/exports/1.csv.gz', true],
            'environment host in upper case with a trailing dot' => [$proxy, 'https://API.didww.com./v3/exports/1.csv.gz', true],
            'base URL with the default port' => ['https://proxy.example.com:443/v3', 'https://proxy.example.com/v3/exports/1.csv.gz', true],
            'base URL with a trailing dot' => ['https://proxy.example.com./v3', 'https://proxy.example.com/v3/exports/1.csv.gz', true],
            'http base URL with the default port' => ['http://proxy.example.com/v3', 'http://proxy.example.com:80/v3/exports/1.csv.gz', true],
            'third host' => [$proxy, 'https://other.example.com/v3/exports/1.csv.gz', false],
            'other environment host' => [$proxy, 'https://sandbox-api.didww.com/v3/exports/1.csv.gz', false],
            'environment host over http' => [$proxy, 'http://api.didww.com/v3/exports/1.csv.gz', false],
            'environment host on another port' => [$proxy, 'https://api.didww.com:8443/v3/exports/1.csv.gz', false],
            'base URL host on another port' => [$proxy, 'https://proxy.example.com:8443/v3/exports/1.csv.gz', false],
            'default https port over http' => [$proxy, 'http://proxy.example.com:443/v3/exports/1.csv.gz', false],
            'relative URL' => [$proxy, '/v3/exports/1.csv.gz', false],
        ];
    }

    #[DataProvider('originProvider')]
    public function testOrigin(string $baseUrl, string $url, bool $allowed)
    {
        $this->configureMock([], new Credentials('test-api-key', 'production'), baseUrl: $baseUrl);
        if ($allowed) {
            $this->http->append(MockHttp::token(), new Response(200, [], 'gzip-bytes'));
        }
        $export = Export::build(self::EXPORT_ID, ['url' => $url]);
        $dest = $this->dir.'/export.csv.gz';

        if ($allowed) {
            $this->assertTrue($export->download($dest));

            return;
        }
        try {
            $export->download($dest);
            $this->fail('Expected an InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('does not match the API base URL', $e->getMessage());
        }
        $this->assertSame(0, $this->http->count());
        $this->assertSame([], glob($this->dir.'/*'));
    }

    public function testMissingUrlThrows()
    {
        $this->configureMock();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Export has no download URL');

        Export::build(self::EXPORT_ID)->download($this->dir.'/export.csv.gz');
    }

    private function export(): Export
    {
        return Export::build(self::EXPORT_ID, ['url' => self::URL]);
    }
}
