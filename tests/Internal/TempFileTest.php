<?php

namespace Didww\Tests\Internal;

use Didww\Internal\TempFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TempFileTest extends TestCase
{
    public static function directoryProvider(): array
    {
        return [
            'absolute path' => ['/var/data/prices.csv.gz', false, '/var/data/'],
            'relative path' => ['data/prices.csv.gz', false, 'data/'],
            'bare name' => ['prices.csv.gz', false, ''],
            'stream wrapper' => ['vfs://root/prices.csv.gz', false, 'vfs://root/'],
            'backslash is a name character on POSIX' => ['a\\prices.csv.gz', false, ''],
            'colon is a name character on POSIX' => ['D:prices.csv.gz', false, ''],
            'Windows absolute path' => ['C:\\data\\prices.csv.gz', true, 'C:\\data\\'],
            'Windows forward slashes' => ['C:/data/prices.csv.gz', true, 'C:/data/'],
            'Windows drive-relative name' => ['D:prices.csv.gz', true, 'D:'],
            'Windows bare name' => ['prices.csv.gz', true, ''],
        ];
    }

    #[DataProvider('directoryProvider')]
    public function testDirectoryOf(string $path, bool $windows, string $directory)
    {
        $this->assertSame($directory, TempFile::directoryOf($path, $windows));
    }

    public function testPathNextTo()
    {
        $this->assertMatchesRegularExpression('#\A/var/data/\.didww-[0-9a-f]{16}\.tmp\z#', TempFile::pathNextTo('/var/data/'.str_repeat('p', 250)));
        $this->assertNotSame(TempFile::pathNextTo('/var/data/prices.csv.gz'), TempFile::pathNextTo('/var/data/prices.csv.gz'));
    }

    public function testPathWithNullByteIsRefused()
    {
        $this->expectException(\ValueError::class);

        TempFile::pathNextTo("/var/data/prices\0.csv.gz");
    }
}
