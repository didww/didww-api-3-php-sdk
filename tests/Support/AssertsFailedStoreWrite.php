<?php

namespace Didww\Tests\Support;

use Didww\Auth\AccessToken;
use Didww\Auth\TokenStore;

/**
 * For the tests of a shared TokenStore: a write that fails throws an
 * unchained exception whose trace keeps no token.
 */
trait AssertsFailedStoreWrite
{
    private function assertFailedWriteKeepsNoToken(TokenStore $store, string $expectedMessage): void
    {
        try {
            TraceArgs::keepingArgs(fn () => $store->set('key', new AccessToken('secret-token', 'Bearer', 900, ['api.didww.com'], 'full', '2026-11-19', new \DateTimeImmutable()), 900));
            $this->fail('No exception');
        } catch (\RuntimeException $e) {
            $this->assertSame($expectedMessage, $e->getMessage());
            $this->assertNull($e->getPrevious());
            $this->assertStringNotContainsString('secret-token', print_r($e->getTrace(), true));
        }
    }
}
