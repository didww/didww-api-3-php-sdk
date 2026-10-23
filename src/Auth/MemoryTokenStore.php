<?php

namespace Didww\Auth;

/**
 * A TokenStore that keeps tokens in process memory; expired entries are
 * dropped when read. It is the default store, shared by every configuration
 * of the process that does not pass its own.
 *
 * Under PHP-FPM or mod_php the process memory lasts one request, so every
 * request mints new tokens; use Psr16TokenStore with a shared cache there.
 */
final class MemoryTokenStore implements TokenStore
{
    /** @var array<string, array{AccessToken, float|null}> */
    private array $entries = [];

    /** @var callable(): float */
    private $clock;

    /**
     * @param (callable(): float)|null $clock monotonic seconds (@internal, for tests)
     */
    public function __construct(?callable $clock = null)
    {
        $this->clock = $clock ?? static fn (): float => hrtime(true) / 1e9;
    }

    public function get(string $key): ?AccessToken
    {
        if (!isset($this->entries[$key])) {
            return null;
        }
        [$token, $expiresAt] = $this->entries[$key];
        if (null !== $expiresAt && ($this->clock)() >= $expiresAt) {
            unset($this->entries[$key]);

            return null;
        }

        return $token;
    }

    public function set(string $key, AccessToken $token, int $ttl): void
    {
        $this->entries[$key] = [$token, $ttl > 0 ? ($this->clock)() + $ttl : null];
    }

    public function delete(string $key): void
    {
        unset($this->entries[$key]);
    }

    public function __debugInfo(): array
    {
        return ['entries' => count($this->entries)];
    }

    public function __toString(): string
    {
        return 'MemoryTokenStore{entries: '.count($this->entries).'}';
    }
}
