<?php

namespace Didww\Internal;

/**
 * A read-only stream wrapper that decompresses gzip data read from another
 * stream, so that functions such as fgetcsv() can read it.
 *
 * Unlike gzopen(), compress.zlib:// and the zlib.inflate filter, which end the
 * data silently at a cut and pass plain data through, it throws
 * \UnexpectedValueException for input that is not gzip, corrupt data
 * (zlib checks the CRC-32 and the length of each member) and input that ends
 * before the trailer of the last member, an empty input included. Members
 * that follow one another are read as one stream. A leading UTF-8 byte order
 * mark of the decompressed data is dropped unless the caller keeps it, and
 * error messages start with a prefix chosen by the caller.
 *
 * @internal
 */
final class GunzipStream
{
    public const PROTOCOL = 'didww-gunzip';
    private const CHUNK_SIZE = 65536;
    private const BOM = "\xEF\xBB\xBF";

    /** @var resource|null set by PHP */
    public $context;

    /** @var resource */
    private $source;
    private \InflateContext $inflate;
    private int $memberBytesIn = 0;
    private bool $memberEnded = false;
    private bool $done = false;
    private bool $bomChecked = false;
    private string $buffer = '';
    // Where the unread part of $buffer starts, so that a read does not copy the rest.
    private int $offset = 0;
    private string $errorPrefix = '';

    /**
     * Opens a stream of the decompressed data of $source, which is read from
     * its current position and left open.
     *
     * @param resource $source
     * @param string   $errorPrefix the start of every error message
     * @param bool     $keepBom     whether a leading UTF-8 byte order mark is kept
     *
     * @return resource
     */
    public static function open($source, string $errorPrefix = 'Price file: ', bool $keepBom = false)
    {
        if (!in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
            stream_wrapper_register(self::PROTOCOL, self::class);
        }
        $context = stream_context_create([self::PROTOCOL => ['source' => $source, 'error_prefix' => $errorPrefix, 'keep_bom' => $keepBom]]);
        $stream = fopen(self::PROTOCOL.'://', 'rb', false, $context);
        if (false === $stream) {
            throw new \RuntimeException('Failed to open the gzip stream');
        }

        return $stream;
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        $contextOptions = stream_context_get_options($this->context)[self::PROTOCOL] ?? [];
        $source = $contextOptions['source'] ?? null;
        if (!is_resource($source) || !str_contains($mode, 'r')) {
            return false;
        }
        $this->source = $source;
        $this->errorPrefix = (string) ($contextOptions['error_prefix'] ?? '');
        $this->bomChecked = (bool) ($contextOptions['keep_bom'] ?? false);
        $this->inflate = self::newInflate();

        return true;
    }

    public function stream_read(int $count): string|false
    {
        while (!$this->done && (strlen($this->buffer) - $this->offset < $count || !$this->bomChecked)) {
            if ($this->offset > 0) {
                $this->buffer = substr($this->buffer, $this->offset);
                $this->offset = 0;
            }
            $this->fill();
            if (!$this->bomChecked && (strlen($this->buffer) >= 3 || $this->done)) {
                if (str_starts_with($this->buffer, self::BOM)) {
                    $this->offset = 3;
                }
                $this->bomChecked = true;
            }
        }
        $chunk = substr($this->buffer, $this->offset, $count);
        $this->offset += strlen($chunk);
        if ($this->offset >= strlen($this->buffer)) {
            $this->buffer = '';
            $this->offset = 0;
        }

        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->done && $this->offset >= strlen($this->buffer);
    }

    public function stream_close(): void
    {
    }

    // Reads and decompresses the next chunk of the source.
    private function fill(): void
    {
        error_clear_last();
        $input = @fread($this->source, self::CHUNK_SIZE);
        if (false === $input) {
            throw new \UnexpectedValueException($this->errorPrefix.(error_get_last()['message'] ?? 'read failed'));
        }
        if ('' === $input && feof($this->source)) {
            if (!$this->memberEnded) {
                throw new \UnexpectedValueException($this->errorPrefix.'unexpected end of gzip data');
            }
            $this->done = true;

            return;
        }
        while ('' !== $input) {
            if ($this->memberEnded) {
                $this->inflate = self::newInflate();
                $this->memberBytesIn = 0;
                $this->memberEnded = false;
            }
            $this->buffer .= $this->inflateAdd($input);
            $read = inflate_get_read_len($this->inflate);
            $consumed = $read - $this->memberBytesIn;
            $this->memberBytesIn = $read;
            if (ZLIB_STREAM_END !== inflate_get_status($this->inflate)) {
                break;
            }
            $this->memberEnded = true;
            $input = (string) substr($input, $consumed);
        }
    }

    private function inflateAdd(string $input): string
    {
        $error = null;
        set_error_handler(static function (int $level, string $message) use (&$error): bool {
            $error = $message;

            return true;
        });
        try {
            $output = inflate_add($this->inflate, $input, ZLIB_SYNC_FLUSH);
        } finally {
            restore_error_handler();
        }
        if (false === $output || null !== $error) {
            throw new \UnexpectedValueException($this->errorPrefix.'not valid gzip data ('.preg_replace('/^inflate_add\(\): /', '', $error ?? 'inflate failed').')');
        }

        return $output;
    }

    private static function newInflate(): \InflateContext
    {
        $context = inflate_init(ZLIB_ENCODING_GZIP);
        if (false === $context) {
            throw new \RuntimeException('Failed to initialise zlib');
        }

        return $context;
    }
}
