<?php

namespace Didww\Tests\Prices\Support;

/**
 * An in-memory user-space stream wrapper without stream_metadata(), like many
 * object storage wrappers: touch() fails on it.
 */
final class NoMetadataStreamWrapper
{
    public const PROTOCOL = 'nometa';

    /** @var array<string, string> */
    public static array $files = [];

    public static bool $failRename = false;

    /** @var resource|null */
    public $context;

    private string $path = '';

    private int $position = 0;

    public static function register(): void
    {
        self::$files = [];
        self::$failRename = false;
        if (!in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
            stream_wrapper_register(self::PROTOCOL, self::class);
        }
    }

    public static function unregister(): void
    {
        if (in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
            stream_wrapper_unregister(self::PROTOCOL);
        }
    }

    public function stream_open(string $path, string $mode): bool
    {
        if (str_starts_with($mode, 'x') && isset(self::$files[$path])) {
            return false;
        }
        $this->path = $path;
        if (str_starts_with($mode, 'r')) {
            return isset(self::$files[$path]);
        }
        self::$files[$path] = '';

        return true;
    }

    public function stream_read(int $count): string
    {
        $chunk = substr(self::$files[$this->path], $this->position, $count);
        $this->position += strlen($chunk);

        return $chunk;
    }

    public function stream_write(string $data): int
    {
        self::$files[$this->path] .= $data;

        return strlen($data);
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen(self::$files[$this->path]);
    }

    public function stream_flush(): bool
    {
        return true;
    }

    public function stream_close(): void
    {
    }

    public function stream_stat(): array
    {
        return ['size' => strlen(self::$files[$this->path])];
    }

    public function url_stat(string $path, int $flags): array|false
    {
        return isset(self::$files[$path]) ? ['size' => strlen(self::$files[$path]), 'mode' => 0100644] : false;
    }

    public function rename(string $from, string $to): bool
    {
        if (self::$failRename || !isset(self::$files[$from])) {
            return false;
        }
        self::$files[$to] = self::$files[$from];
        unset(self::$files[$from]);

        return true;
    }

    public function unlink(string $path): bool
    {
        unset(self::$files[$path]);

        return true;
    }
}
