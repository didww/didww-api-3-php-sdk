<?php

namespace Didww\Tests\Prices;

use Didww\Internal\GunzipStream;
use Didww\Prices\PriceFileReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PriceFileReaderTest extends TestCase
{
    private static function fixturePath(string $name): string
    {
        return __DIR__.'/../fixtures/prices/'.$name;
    }

    /**
     * @return resource
     */
    private static function stream(string $bytes)
    {
        $stream = fopen('php://memory', 'w+b');
        fwrite($stream, $bytes);
        rewind($stream);

        return $stream;
    }

    private static function rows($gzipped): array
    {
        return iterator_to_array(PriceFileReader::rows($gzipped), false);
    }

    public function testVoiceInFile()
    {
        $rows = self::rows(self::fixturePath('voice_in_prices.csv.gz'));

        $this->assertCount(4, $rows);
        $this->assertSame([
            'Country' => 'United Kingdom',
            'Src country name' => '',
            'Source type' => 'cellular',
            'Destination' => '44800',
            'Rate (USD, excl. VAT)' => '',
            'Connection fee (USD, excl. VAT)' => '',
            'Initial interval' => '60',
            'Next interval' => '60',
            'Reject calls' => 'true',
        ], $rows[0]);
        $this->assertSame(['44800', '4480012', '1800', '1800'], array_column($rows, 'Destination'));
    }

    public function testVoiceOutFile()
    {
        $rows = self::rows(self::fixturePath('voice_out_prices.csv.gz'));

        $this->assertCount(4, $rows);
        $this->assertSame([
            'Category' => 'International',
            'Country' => 'Germany',
            'Network' => 'Fixed',
            'Prefix' => '4930',
            'Rate' => '0.0045',
            'Connection fee' => '0.0',
            'Initial Increment' => '1',
            'Recurring Increment' => '1',
            'Reject calls' => 'false',
            'Next rate' => '0.005',
            'Next connection fee' => '0.0',
            'Next initial increment' => '30',
            'Next recurring increment' => '6',
            'Effective date' => '2026-11-01T00:00:00Z',
            'Comment' => 'Increase and Billing increment change',
        ], $rows[0]);
        $this->assertSame('Origin based', $rows[1]['Category']);
        $this->assertSame('99950,99951,99952', $rows[2]['Prefix']);
        $this->assertSame('New Breakout', $rows[3]['Network']);
        $this->assertSame('', $rows[3]['Rate']);
        $this->assertSame('true', $rows[3]['Reject calls']);
    }

    public function testReadsStreamFromCurrentPositionAndLeavesItOpen()
    {
        $stream = self::stream('skip'.gzencode("Country,Rate\nUS,0.1\n"));
        fseek($stream, 4);

        $rows = self::rows($stream);

        $this->assertSame([['Country' => 'US', 'Rate' => '0.1']], $rows);
        $this->assertTrue(is_resource($stream));
        fclose($stream);
    }

    public static function csvProvider(): array
    {
        return [
            'byte order mark' => ["\xEF\xBB\xBFCountry,Rate\nUS,0.1\n", [['Country' => 'US', 'Rate' => '0.1']]],
            'byte order mark before a quoted header' => ["\xEF\xBB\xBF\"Country\",Rate\nUS,0.1\n", [['Country' => 'US', 'Rate' => '0.1']]],
            'CRLF line ends' => ["Country,Rate\r\nUS,0.1\r\nDE,0.2\r\n", [['Country' => 'US', 'Rate' => '0.1'], ['Country' => 'DE', 'Rate' => '0.2']]],
            'empty lines' => ["Country,Rate\n\nUS,0.1\n\n", [['Country' => 'US', 'Rate' => '0.1']]],
            'no final line end' => ["Country,Rate\nUS,0.1", [['Country' => 'US', 'Rate' => '0.1']]],
            'quoted line break and quote' => ["Prefix,Comment\n\"1,2\",\"a\nb \"\"c\"\"\"\n", [['Prefix' => '1,2', 'Comment' => "a\nb \"c\""]]],
            'backslash is no escape' => ["Comment,Rate\n\"a\\\",0.1\n", [['Comment' => 'a\\', 'Rate' => '0.1']]],
            'header only' => ["Country,Rate\n", []],
            'empty CSV' => ['', []],
        ];
    }

    #[DataProvider('csvProvider')]
    public function testCsv(string $csv, array $rows)
    {
        $this->assertSame($rows, self::rows(self::stream(gzencode($csv))));
    }

    public function testConsecutiveGzipMembers()
    {
        $rows = self::rows(self::stream(gzencode("Country,Rate\nUS,").gzencode("0.1\nDE,0.2\n")));

        $this->assertSame([['Country' => 'US', 'Rate' => '0.1'], ['Country' => 'DE', 'Rate' => '0.2']], $rows);
    }

    public function testLargeFile()
    {
        $csv = "Prefix,Rate\n";
        for ($i = 0; $i < 50000; ++$i) {
            $csv .= $i.',"0.'.$i."\"\n";
        }

        $count = 0;
        foreach (PriceFileReader::rows(self::stream(gzencode($csv))) as $row) {
            ++$count;
        }

        $this->assertSame(50000, $count);
        $this->assertSame(['Prefix' => '49999', 'Rate' => '0.49999'], $row);
    }

    public function testHighlyCompressibleDataIsReadInLinearTime()
    {
        // 24 MB that compress into a single input chunk.
        $source = self::stream(gzencode(str_repeat("1,2\n", 6000000), 9));
        $started = microtime(true);

        $stream = GunzipStream::open($source);
        $bytes = 0;
        while (!feof($stream)) {
            $bytes += strlen(fread($stream, 8192));
        }
        fclose($stream);

        $this->assertSame(24000000, $bytes);
        $this->assertLessThan(1, microtime(true) - $started, 'a read copies only the bytes it returns');
    }

    public static function invalidInputProvider(): array
    {
        $gzip = file_get_contents(self::fixturePath('voice_in_prices.csv.gz'));

        return [
            'uncompressed input' => ["Country,Destination\nGermany,49\n", 'Price file: not valid gzip data'],
            'truncated trailer' => [substr($gzip, 0, -1), 'Price file: unexpected end of gzip data'],
            'missing trailer' => [substr($gzip, 0, -8), 'Price file: unexpected end of gzip data'],
            'cut in the data' => [substr($gzip, 0, intdiv(strlen($gzip), 2)), 'Price file: unexpected end of gzip data'],
            'empty input' => ['', 'Price file: unexpected end of gzip data'],
            'gzip header only' => [substr($gzip, 0, 10), 'Price file: unexpected end of gzip data'],
            'corrupt length' => [substr($gzip, 0, -4).pack('V', 1), 'Price file: not valid gzip data'],
            'trailing garbage' => [$gzip."\0\0\0\0", 'Price file: not valid gzip data'],
            'wrong number of fields' => [gzencode("Country,Rate\nUS,0.1\nDE,0.2,x\n"), 'Price file: record 2: wrong number of fields'],
        ];
    }

    #[DataProvider('invalidInputProvider')]
    public function testInvalidInput(string $bytes, string $message)
    {
        try {
            self::rows(self::stream($bytes));
            $this->fail('expected an exception');
        } catch (\UnexpectedValueException $e) {
            $this->assertStringStartsWith($message, $e->getMessage());
        }
    }

    public function testRowsBeforeAnErrorAreYielded()
    {
        $rows = [];
        try {
            foreach (PriceFileReader::rows(self::stream(gzencode("Country,Rate\nUS,0.1\nDE\n"))) as $row) {
                $rows[] = $row;
            }
            $this->fail('expected an exception');
        } catch (\UnexpectedValueException) {
        }

        $this->assertSame([['Country' => 'US', 'Rate' => '0.1']], $rows);
    }

    public function testConsumerExceptionPropagates()
    {
        $stop = new \LogicException('stop');
        $calls = 0;

        try {
            foreach (PriceFileReader::rows(self::fixturePath('voice_in_prices.csv.gz')) as $row) {
                ++$calls;
                throw $stop;
            }
        } catch (\LogicException $e) {
            $this->assertSame($stop, $e);
        }
        $this->assertSame(1, $calls);
    }

    public function testEarlyBreakClosesTheFile()
    {
        $before = count(get_resources('stream'));
        $rows = PriceFileReader::rows(self::fixturePath('voice_in_prices.csv.gz'));
        foreach ($rows as $row) {
            $this->assertGreaterThan($before, count(get_resources('stream')));
            break;
        }

        unset($rows);

        $this->assertSame($before, count(get_resources('stream')));
    }

    public function testMissingFile()
    {
        $this->expectException(\RuntimeException::class);

        self::rows(self::fixturePath('missing.csv.gz'));
    }

    public static function invalidArgumentProvider(): array
    {
        return ['empty path' => [''], 'null' => [null], 'number' => [1]];
    }

    #[DataProvider('invalidArgumentProvider')]
    public function testInvalidArgument($gzipped)
    {
        $this->expectException(\InvalidArgumentException::class);

        PriceFileReader::rows($gzipped);
    }
}
