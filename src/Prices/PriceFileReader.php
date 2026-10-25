<?php

namespace Didww\Prices;

use Didww\Internal\GunzipStream;

/**
 * Reads a price file as downloaded (gzipped CSV), one row at a time.
 */
final class PriceFileReader
{
    private function __construct()
    {
    }

    /**
     * Yields the rows of a gzipped price file, each keyed by the header line
     * as is (for example "Rate (USD, excl. VAT)").
     *
     * A UTF-8 byte order mark is skipped, empty lines are skipped and a file
     * with only a header has no rows; bytes are not transcoded. Input that is
     * not gzip, gzip data cut short (an empty input included) and a record
     * whose field count differs from the header throw
     * \UnexpectedValueException. The rows are read lazily, so rows before the
     * error have already been yielded. CSV quoting follows fgetcsv() without an
     * escape character, which accepts a stray quote in an unquoted field.
     *
     * A file opened from a path is closed when the generator finishes or is
     * destroyed; a stream is read from its current position and left open.
     *
     * @param string|resource $gzipped a file path or a readable stream
     *
     * @return \Generator<int, array<string, string>>
     *
     * @throws \InvalidArgumentException when $gzipped is neither a path nor a stream
     * @throws \RuntimeException         when the rows are first read and the path cannot be opened
     */
    public static function rows($gzipped): \Generator
    {
        if (!(is_string($gzipped) && '' !== $gzipped) && !is_resource($gzipped)) {
            throw new \InvalidArgumentException('Price file must be a file path or a readable stream');
        }

        return self::read($gzipped);
    }

    /**
     * @param string|resource $gzipped
     */
    private static function read($gzipped): \Generator
    {
        $source = $gzipped;
        $stream = null;
        try {
            if (is_string($gzipped)) {
                error_clear_last();
                $source = @fopen($gzipped, 'rb');
                if (false === $source) {
                    throw new \RuntimeException(error_get_last()['message'] ?? 'Failed to open '.$gzipped);
                }
            }
            $stream = GunzipStream::open($source);
            $header = null;
            $record = 0;
            while (false !== ($fields = fgetcsv($stream, null, ',', '"', ''))) {
                if ([null] === $fields) {
                    continue;
                }
                if (null === $header) {
                    $header = $fields;
                    continue;
                }
                ++$record;
                if (count($fields) !== count($header)) {
                    throw new \UnexpectedValueException(sprintf('Price file: record %d: wrong number of fields', $record));
                }
                yield array_combine($header, $fields);
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if (is_string($gzipped) && is_resource($source)) {
                fclose($source);
            }
        }
    }
}
