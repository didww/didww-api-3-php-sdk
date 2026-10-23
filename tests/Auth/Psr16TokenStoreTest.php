<?php

namespace Didww\Tests\Auth;

use Didww\Auth\AccessToken;
use Didww\Auth\Psr16TokenStore;
use Didww\Auth\TokenProvider;
use Didww\Tests\Support\ArrayCache;
use Didww\Tests\Support\TraceArgs;
use PHPUnit\Framework\TestCase;

class Psr16TokenStoreTest extends TestCase
{
    private const KEY = TokenProvider::CACHE_KEY_PREFIX.'2e1c5435f0e67f91d34497fabf69840a945923a47982fad172c353b3f26be539';

    public function testRoundTrip()
    {
        $cache = new ArrayCache();
        $store = new Psr16TokenStore($cache);
        $token = new AccessToken('t1', 'Bearer', 900, ['api.didww.com'], 'full', '2026-10-22', new \DateTimeImmutable('2026-10-22T10:00:00+00:00'));

        $store->set(self::KEY, $token, 900);

        $this->assertEquals($token, $store->get(self::KEY));
        $cacheKey = array_key_first($cache->values);
        $this->assertMatchesRegularExpression('/\Adidww_token_v1_[0-9a-f]{48}\z/', $cacheKey);
        $this->assertSame(Psr16TokenStore::cacheKey(self::KEY), $cacheKey);
        $this->assertSame(900, $cache->ttls[$cacheKey]);
        $this->assertIsArray($cache->values[$cacheKey]);
    }

    public function testFailedWriteKeepsNoToken()
    {
        $cache = new class extends ArrayCache {
            public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
            {
                throw new \RuntimeException('cache unavailable');
            }
        };
        $store = new Psr16TokenStore($cache);

        try {
            TraceArgs::keepingArgs(fn () => $store->set(self::KEY, new AccessToken('secret-token', 'Bearer', 900, ['api.didww.com'], 'full', '2026-10-22', new \DateTimeImmutable()), 900));
            $this->fail('No exception');
        } catch (\RuntimeException $e) {
            $this->assertSame('RuntimeException: cache unavailable', $e->getMessage());
            $this->assertNull($e->getPrevious());
            $this->assertStringNotContainsString('secret-token', print_r($e->getTrace(), true));
        }
    }

    public function testTtlZeroMeansNoExpiry()
    {
        $cache = new ArrayCache();
        $store = new Psr16TokenStore($cache);

        $store->set(self::KEY, new AccessToken('t1', 'Bearer', 0, ['api.didww.com'], 'full', '2026-10-22', new \DateTimeImmutable()), 0);

        $this->assertNull($cache->ttls[Psr16TokenStore::cacheKey(self::KEY)]);
    }

    public function testNonArrayValueIsAMiss()
    {
        $cache = new ArrayCache();
        $cache->set(Psr16TokenStore::cacheKey(self::KEY), 'garbage');

        $this->assertNull((new Psr16TokenStore($cache))->get(self::KEY));
        $this->assertNull((new Psr16TokenStore($cache))->get('other'));
    }

    public function testTokenIsASensitiveParameter()
    {
        $this->assertNotEmpty((new \ReflectionParameter([Psr16TokenStore::class, 'set'], 'token'))->getAttributes(\SensitiveParameter::class));
    }

    public function testDelete()
    {
        $cache = new ArrayCache();
        $store = new Psr16TokenStore($cache);
        $store->set(self::KEY, new AccessToken('t1', 'Bearer', 900, ['api.didww.com'], 'full', '2026-10-22', new \DateTimeImmutable()), 900);

        $store->delete(self::KEY);

        $this->assertNull($store->get(self::KEY));
        $this->assertSame([], $cache->values);
    }
}
