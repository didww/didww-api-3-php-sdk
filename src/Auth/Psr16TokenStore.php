<?php

namespace Didww\Auth;

use Psr\SimpleCache\CacheInterface;

/**
 * A TokenStore backed by a PSR-16 cache (requires psr/simple-cache), for
 * example APCu or Redis through symfony/cache, so that PHP-FPM workers and
 * short-lived processes share tokens instead of minting one per request.
 *
 * Tokens are stored as arrays (AccessToken::toArray()) under keys mapped to
 * "didww_token_v1_" plus 48 hex digits, which fit the PSR-16 key rules. A
 * cache that answers false to set() or delete() makes them throw.
 */
final class Psr16TokenStore implements TokenStore
{
    private const KEY_PREFIX = 'didww_token_v1_';

    public function __construct(private readonly CacheInterface $cache)
    {
    }

    public function get(string $key): ?AccessToken
    {
        $value = $this->cache->get(self::cacheKey($key));

        return is_array($value) ? AccessToken::fromArray($value) : null;
    }

    public function set(string $key, #[\SensitiveParameter] AccessToken $token, int $ttl): void
    {
        try {
            $stored = $this->cache->set(self::cacheKey($key), $token->toArray(), $ttl > 0 ? $ttl : null);
        } catch (\Throwable $e) {
            // Its trace holds the token as a plain array, so it is not chained.
            throw new \RuntimeException($e::class.': '.$e->getMessage());
        }
        if (false === $stored) {
            throw new \UnexpectedValueException('The PSR-16 cache did not store the token');
        }
    }

    public function delete(string $key): void
    {
        if (false === $this->cache->delete(self::cacheKey($key))) {
            throw new \UnexpectedValueException('The PSR-16 cache did not delete the token');
        }
    }

    public function __debugInfo(): array
    {
        return ['cache' => get_class($this->cache)];
    }

    /**
     * @internal
     */
    public static function cacheKey(string $key): string
    {
        return self::KEY_PREFIX.substr(hash('sha256', $key), 0, 48);
    }
}
