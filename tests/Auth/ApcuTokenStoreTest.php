<?php

namespace Didww\Tests\Auth;

use Didww\Auth\AccessToken;
use Didww\Auth\ApcuTokenStore;
use Didww\Auth\MemoryTokenStore;
use Didww\Auth\TokenProvider;
use Didww\Configuration;
use Didww\Tests\Support\AssertsFailedStoreWrite;
use PHPUnit\Framework\TestCase;

class ApcuTokenStoreTest extends TestCase
{
    use AssertsFailedStoreWrite;

    private const KEY = TokenProvider::CACHE_KEY_PREFIX.'2e1c5435f0e67f91d34497fabf69840a945923a47982fad172c353b3f26be539';

    public function testRoundTrip()
    {
        $this->requireApcu();
        $store = new ApcuTokenStore();
        $token = new AccessToken('t1', 'Bearer', 3600, ['api.didww.com'], 'full', '2026-11-19', new \DateTimeImmutable('2026-11-19T10:00:00+00:00'));

        $store->set(self::KEY, $token, 3600);

        $this->assertEquals($token, $store->get(self::KEY));
        $this->assertIsArray(apcu_fetch(self::KEY));
        $this->assertSame(3600, apcu_key_info(self::KEY)['ttl']);
    }

    public function testTtlZeroMeansNoExpiry()
    {
        $this->requireApcu();
        (new ApcuTokenStore())->set(self::KEY, new AccessToken('t1', 'Bearer', 0, ['api.didww.com'], 'full', '2026-11-19', new \DateTimeImmutable()), 0);

        $this->assertSame(0, apcu_key_info(self::KEY)['ttl']);
    }

    public function testNonArrayValueIsAMiss()
    {
        $this->requireApcu();
        apcu_store(self::KEY, 'garbage');

        $this->assertNull((new ApcuTokenStore())->get(self::KEY));
        $this->assertNull((new ApcuTokenStore())->get('other'));
    }

    public function testDelete()
    {
        $this->requireApcu();
        $store = new ApcuTokenStore();
        $store->set(self::KEY, new AccessToken('t1', 'Bearer', 3600, ['api.didww.com'], 'full', '2026-11-19', new \DateTimeImmutable()), 3600);

        $store->delete(self::KEY);
        $store->delete('missing');

        $this->assertNull($store->get(self::KEY));
    }

    public function testTokenIsASensitiveParameter()
    {
        $this->assertNotEmpty((new \ReflectionParameter([ApcuTokenStore::class, 'set'], 'token'))->getAttributes(\SensitiveParameter::class));
    }

    public function testFailedWriteKeepsNoToken()
    {
        $store = new ApcuTokenStore(static fn (): bool => throw new \RuntimeException('cache unavailable'));

        $this->assertFailedWriteKeepsNoToken($store, 'RuntimeException: cache unavailable');
    }

    public function testWriteAnsweredFalseThrows()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('APCu did not store the token');

        (new ApcuTokenStore(static fn (): bool => false))->set(self::KEY, new AccessToken('t1', 'Bearer', 3600, ['api.didww.com'], 'full', '2026-11-19', new \DateTimeImmutable()), 3600);
    }

    public function testAvailableOnlyOutsideTheCliWithApcuEnabled()
    {
        $this->assertSame(self::apcuEnabled(), ApcuTokenStore::isAvailable('fpm-fcgi'));
        $this->assertFalse(ApcuTokenStore::isAvailable('cli'));
        $this->assertFalse(ApcuTokenStore::isAvailable('phpdbg'));
    }

    public function testDefaultStoreOfWebRequests()
    {
        $newDefault = new \ReflectionMethod(Configuration::class, 'newDefaultTokenStore');

        $this->assertInstanceOf(self::apcuEnabled() ? ApcuTokenStore::class : MemoryTokenStore::class, $newDefault->invoke(null, 'fpm-fcgi'));
        $this->assertInstanceOf(MemoryTokenStore::class, $newDefault->invoke(null, 'cli'));
    }

    private function requireApcu(): void
    {
        if (!self::apcuEnabled()) {
            $this->markTestSkipped('APCu is not enabled in the CLI (apc.enable_cli)');
        }
        apcu_clear_cache();
    }

    private static function apcuEnabled(): bool
    {
        return \function_exists('apcu_enabled') && apcu_enabled();
    }
}
