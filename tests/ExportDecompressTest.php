<?php

namespace Didww\Tests;

use Didww\Auth\TokenProvider;
use Didww\Item\Export;
use Didww\Tests\Support\ConfiguresMockHttp;
use Didww\Tests\Support\MockHttp;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExportDecompressTest extends TestCase
{
    use ConfiguresMockHttp;

    private const EXPORT_ID = '9df99644-f1a5-4a3c-99a4-559d758eb96b';
    private const URL = 'https://sandbox-api.didww.com/v3/exports/9df99644-f1a5-4a3c-99a4-559d758eb96b.csv.gz';
    private const CSV = "\xEF\xBB\xBFDate/Time Start (UTC),Source\n2026-10-01 10:00:00,15551234567\n";

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

    public function testValidGzipIsWrittenDecompressed()
    {
        $this->configureMock()->append(MockHttp::token(), new Response(200, ['Content-Type' => 'application/gzip'], gzencode(self::CSV)));
        $dest = $this->dir.'/export.csv';
        file_put_contents($dest, 'previous');

        $this->assertTrue($this->export()->downloadAndDecompress($dest));

        $this->assertSame(self::CSV, file_get_contents($dest), 'the bytes are kept as they are, a byte order mark included');
        $this->assertSame([$dest], glob($this->dir.'/*'), 'no temporary file is left behind');
        $this->assertFalse($this->http->options(1)['decode_content']);
    }

    public function testMembersAreReadAsOneFile()
    {
        $this->configureMock()->append(MockHttp::token(), new Response(200, [], gzencode("a,b\n").gzencode("1,2\n")));
        $dest = $this->dir.'/export.csv';

        $this->assertTrue($this->export()->downloadAndDecompress($dest));

        $this->assertSame("a,b\n1,2\n", file_get_contents($dest));
    }

    public function testValidGzipToStream()
    {
        $this->configureMock()->append(MockHttp::token(), new Response(200, [], gzencode(self::CSV)));
        $stream = fopen('php://memory', 'w+b');

        $this->assertTrue($this->export()->downloadAndDecompress($stream));

        rewind($stream);
        $this->assertSame(self::CSV, stream_get_contents($stream));
    }

    public static function invalidBodyProvider(): array
    {
        $gzip = gzencode(self::CSV.str_repeat("2026-10-01 10:00:01,15551234568\n", 100));

        return [
            'truncated gzip' => [substr($gzip, 0, intdiv(strlen($gzip), 2)), 'Export decompression failed: unexpected end of gzip data'],
            'missing trailer' => [substr($gzip, 0, -8), 'Export decompression failed: unexpected end of gzip data'],
            'empty body' => ['', 'Export decompression failed: unexpected end of gzip data'],
            'non-gzip body' => [self::CSV, 'Export decompression failed: not valid gzip data'],
            'corrupt checksum' => [substr($gzip, 0, -8).pack('V', 0).substr($gzip, -4), 'Export decompression failed: not valid gzip data'],
        ];
    }

    #[DataProvider('invalidBodyProvider')]
    public function testInvalidBodyKeepsTheDestination(string $body, string $message)
    {
        $this->configureMock()->append(MockHttp::token(), new Response(200, [], $body));
        $dest = $this->dir.'/export.csv';
        file_put_contents($dest, 'previous');

        $result = $this->export()->downloadAndDecompress($dest);

        $this->assertIsString($result);
        $this->assertStringStartsWith($message, $result);
        $this->assertSame('previous', file_get_contents($dest));
        $this->assertSame([$dest], glob($this->dir.'/*'), 'no partial or temporary file is left behind');
    }

    #[DataProvider('invalidBodyProvider')]
    public function testInvalidBodyCreatesNoDestination(string $body, string $message)
    {
        $this->configureMock()->append(MockHttp::token(), new Response(200, [], $body));
        $dest = $this->dir.'/export.csv';

        $this->assertStringStartsWith($message, (string) $this->export()->downloadAndDecompress($dest));

        $this->assertSame([], glob($this->dir.'/*'));
    }

    #[DataProvider('invalidBodyProvider')]
    public function testInvalidBodyLeavesTheStreamUnwritten(string $body, string $message)
    {
        $this->configureMock()->append(MockHttp::token(), new Response(200, [], $body));
        $stream = fopen('php://memory', 'w+b');

        $this->assertStringStartsWith($message, (string) $this->export()->downloadAndDecompress($stream));

        rewind($stream);
        $this->assertSame('', stream_get_contents($stream));
    }

    public function testDownloadFailureKeepsTheDestination()
    {
        $this->configureMock()->append(MockHttp::token(), new Response(404, [], 'Not Found'));
        $dest = $this->dir.'/export.csv';
        file_put_contents($dest, 'previous');

        $this->assertSame('Export download failed with status 404', $this->export()->downloadAndDecompress($dest));

        $this->assertSame('previous', file_get_contents($dest));
        $this->assertSame([$dest], glob($this->dir.'/*'));
    }

    public function testMissingDirectoryReturnsMessageBeforeDownloading()
    {
        $this->configureMock();

        $this->assertSame('Failed to open destination file for writing', $this->export()->downloadAndDecompress($this->dir.'/missing/export.csv'));
        $this->assertSame(0, $this->http->count());
    }

    public function testDownloadedGzipIsPrivate()
    {
        $tmpDir = sys_get_temp_dir();
        $before = glob($tmpDir.'/didww_export_*') ?: [];
        $modes = [];
        $this->configureMock()->append(MockHttp::token(), new Response(200, [], gzencode(self::CSV)));
        $this->http->stack->push(Middleware::tap(static function () use ($tmpDir, $before, &$modes): void {
            foreach (array_diff(glob($tmpDir.'/didww_export_*') ?: [], $before) as $file) {
                $modes[basename($file)] = fileperms($file) & 0777;
            }
        }));
        // A umask that lets the group read new files, which the downloaded
        // gzip must not follow.
        $umask = umask(0027);
        try {
            $this->assertTrue($this->export()->downloadAndDecompress($this->dir.'/export.csv'));
        } finally {
            umask($umask);
        }

        $this->assertNotEmpty($modes);
        foreach ($modes as $file => $mode) {
            $this->assertSame(0600, $mode, $file.' is readable by other users');
        }
    }

    private function export(): Export
    {
        return Export::build(self::EXPORT_ID, ['url' => self::URL]);
    }
}
