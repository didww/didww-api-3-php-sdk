<?php

namespace Didww\Tests\Support;

use Didww\Auth\AccessToken;
use Didww\Auth\MemoryTokenStore;
use Didww\Auth\TokenStore;

/**
 * A MemoryTokenStore that records the keys and TTLs it is given.
 */
final class RecordingTokenStore implements TokenStore
{
    /** @var list<array{string, int}> */
    public array $sets = [];
    private MemoryTokenStore $inner;

    public function __construct()
    {
        $this->inner = new MemoryTokenStore();
    }

    public function get(string $key): ?AccessToken
    {
        return $this->inner->get($key);
    }

    public function set(string $key, AccessToken $token, int $ttl): void
    {
        $this->sets[] = [$key, $ttl];
        $this->inner->set($key, $token, $ttl);
    }

    public function delete(string $key): void
    {
        $this->inner->delete($key);
    }
}
