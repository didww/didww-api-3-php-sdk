<?php

namespace Didww\Tests\Support;

use Didww\Auth\AccessToken;
use Didww\Auth\MemoryTokenStore;
use Didww\Auth\TokenStore;

/**
 * A TokenStore whose calls throw.
 */
final class FailingTokenStore implements TokenStore
{
    private MemoryTokenStore $inner;

    public function __construct(private bool $failGet = true, private bool $failSet = true)
    {
        $this->inner = new MemoryTokenStore();
    }

    public function get(string $key): ?AccessToken
    {
        if ($this->failGet) {
            throw new \RuntimeException('store down');
        }

        return $this->inner->get($key);
    }

    public function set(string $key, AccessToken $token, int $ttl): void
    {
        if ($this->failSet) {
            throw new \RuntimeException('store down');
        }
        $this->inner->set($key, $token, $ttl);
    }

    public function delete(string $key): void
    {
        throw new \RuntimeException('store down');
    }
}
