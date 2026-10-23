<?php

namespace Didww\Tests\Auth;

use Didww\Auth\AccessToken;
use Didww\Auth\MemoryTokenStore;
use PHPUnit\Framework\TestCase;

class MemoryTokenStoreTest extends TestCase
{
    private float $now = 100.0;

    public function testGetMissing()
    {
        $this->assertNull($this->store()->get('missing'));
    }

    public function testExpiry()
    {
        $store = $this->store();
        $store->set('k', $this->token('t1'), 60);

        $this->now += 59.5;
        $this->assertSame('t1', $store->get('k')->getAccessToken());

        $this->now += 0.5;
        $this->assertNull($store->get('k'));
        $this->assertSame(['entries' => 0], $store->__debugInfo());
    }

    public function testWithoutTtl()
    {
        $store = $this->store();
        $store->set('k', $this->token('t1'), 0);

        $this->now += 1e9;
        $this->assertSame('t1', $store->get('k')->getAccessToken());
    }

    public function testReplace()
    {
        $store = $this->store();
        $store->set('k', $this->token('t1'), 60);
        $store->set('k', $this->token('t2'), 60);

        $this->assertSame('t2', $store->get('k')->getAccessToken());
    }

    public function testDelete()
    {
        $store = $this->store();
        $store->set('k', $this->token('t1'), 60);

        $store->delete('k');
        $store->delete('k');

        $this->assertNull($store->get('k'));
    }

    public function testDebugOutputHidesTokens()
    {
        $store = $this->store();
        $store->set('k', $this->token('secret-token-value'), 60);

        $this->assertStringNotContainsString('secret-token-value', print_r($store, true));
        $this->assertSame('MemoryTokenStore{entries: 1}', (string) $store);
    }

    private function store(): MemoryTokenStore
    {
        return new MemoryTokenStore(fn () => $this->now);
    }

    private function token(string $accessToken): AccessToken
    {
        return new AccessToken($accessToken, 'Bearer', 60, ['api.didww.com'], 'full', '2026-10-22', new \DateTimeImmutable());
    }
}
