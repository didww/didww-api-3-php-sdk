<?php

namespace Didww\Auth;

/**
 * A TokenStore in APCu, the shared memory of one PHP-FPM or mod_php server, so
 * that a web request reuses the tokens minted by earlier ones. It is the
 * default store wherever APCu is enabled outside the CLI.
 *
 * APCu memory is shared by every pool of one PHP-FPM master: where the sites
 * of other owners run under the same master, pass a store of your own.
 */
final class ApcuTokenStore implements TokenStore
{
    /** @var callable(string, array, int): bool */
    private $store;

    /**
     * @param (callable(string, array, int): bool)|null $store apcu_store (@internal, for tests)
     */
    public function __construct(?callable $store = null)
    {
        $this->store = $store ?? static fn (string $key, #[\SensitiveParameter] array $value, int $ttl): bool => apcu_store($key, $value, $ttl);
    }

    /**
     * Whether APCu can share tokens between requests: it is enabled, and the
     * process serves web requests (in the CLI and phpdbg each process has its
     * own APCu).
     *
     * @param string $sapi the server API (@internal, for tests)
     */
    public static function isAvailable(string $sapi = \PHP_SAPI): bool
    {
        return !\in_array($sapi, ['cli', 'phpdbg'], true) && \function_exists('apcu_enabled') && apcu_enabled();
    }

    public function get(string $key): ?AccessToken
    {
        $value = apcu_fetch($key, $success);

        return $success && is_array($value) ? AccessToken::fromArray($value) : null;
    }

    public function set(string $key, #[\SensitiveParameter] AccessToken $token, int $ttl): void
    {
        try {
            $stored = ($this->store)($key, $token->toArray(), max($ttl, 0));
        } catch (\Throwable $e) {
            // Its trace holds the token as a plain array, so it is not chained.
            throw new \RuntimeException($e::class.': '.$e->getMessage());
        }
        if (!$stored) {
            throw new \UnexpectedValueException('APCu did not store the token');
        }
    }

    public function delete(string $key): void
    {
        apcu_delete($key);
    }
}
