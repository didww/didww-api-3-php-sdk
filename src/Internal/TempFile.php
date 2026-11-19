<?php

namespace Didww\Internal;

/**
 * @internal
 */
final class TempFile
{
    private function __construct()
    {
        // Static helpers only; not meant to be instantiated.
    }

    /**
     * A new random path for the temporary file of a download to $path:
     * `.didww-<16 hex digits>.tmp` in the directory of $path, so that the
     * rename to $path is atomic, with a name that fits however long the name
     * of $path is.
     *
     * @throws \ValueError when $path contains a null byte, before any request
     */
    public static function pathNextTo(string $path): string
    {
        if (str_contains($path, "\0")) {
            throw new \ValueError('The destination path must not contain any null bytes');
        }

        return self::directoryOf($path, '\\' === DIRECTORY_SEPARATOR).'.didww-'.bin2hex(random_bytes(8)).'.tmp';
    }

    /**
     * The directory part of $path, ending with its separator, or empty for a
     * bare file name. On Windows a backslash separates too, and a drive
     * designator ("D:export.csv") is kept, so that the file stays on that drive.
     */
    public static function directoryOf(string $path, bool $windows): string
    {
        $directory = substr($path, 0, strlen($path) - strcspn(strrev($path), $windows ? '/\\' : '/'));
        if ('' === $directory && $windows && preg_match('/\A[A-Za-z]:/', $path)) {
            return substr($path, 0, 2);
        }

        return $directory;
    }
}
