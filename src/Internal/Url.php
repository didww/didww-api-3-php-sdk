<?php

namespace Didww\Internal;

/**
 * @internal
 */
final class Url
{
    private function __construct()
    {
        // Static helpers only; not meant to be instantiated.
    }

    /**
     * Whether $url is an http or https URL with a host and without a query or
     * fragment, which would swallow the paths appended to it.
     */
    public static function isHttpUrl(string $url): bool
    {
        if (false !== strpbrk($url, '?#') || preg_match('/[\x00-\x20\x7F]/', $url)) {
            return false;
        }
        $parts = parse_url($url);

        return is_array($parts)
            && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && '' !== ($parts['host'] ?? '');
    }

    /**
     * $base without a trailing slash, ending with /v3.
     */
    public static function withV3(string $base): string
    {
        $base = rtrim($base, '/');

        return str_ends_with($base, '/v3') ? $base : $base.'/v3';
    }

    /**
     * $path appended to $base, adding /v3 when $base does not end with it.
     */
    public static function joinV3(string $base, string $path): string
    {
        return self::withV3($base).'/'.ltrim($path, '/');
    }

    /**
     * Whether $a and $b have the same scheme, host name (ignoring case and a
     * trailing dot) and port (the scheme's default port when none is given).
     */
    public static function sameOrigin(string $a, string $b): bool
    {
        $x = self::originParts($a);
        $y = self::originParts($b);

        return null !== $x && $x === $y;
    }

    /**
     * "scheme://host[:port]" of $url for messages, or $url itself when it has
     * no scheme or host.
     */
    public static function origin(string $url): string
    {
        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }

    /**
     * @return array{string, string, int}|null
     */
    private static function originParts(string $url): ?array
    {
        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }
        $scheme = strtolower($parts['scheme']);
        $port = $parts['port'] ?? match ($scheme) {
            'https' => 443,
            'http' => 80,
            default => 0,
        };

        return [$scheme, rtrim(strtolower($parts['host']), '.'), $port];
    }
}
