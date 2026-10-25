<?php

namespace Didww\Tests\Support;

use Didww\Auth\AccessToken;
use Didww\Auth\Audience;
use Didww\Auth\TokenStore;

/**
 * Returns the same token for every key, so that recorded requests need no
 * token service interaction.
 */
final class FixedTokenStore implements TokenStore
{
    public const ACCESS_TOKEN = 'test-access-token';

    public function get(string $key): ?AccessToken
    {
        return new AccessToken(
            self::ACCESS_TOKEN,
            'Bearer',
            900,
            [Audience::API, Audience::VOICE_IN, Audience::VOICE_OUT, Audience::SMS],
            'full',
            '2026-10-22',
            new \DateTimeImmutable('2026-10-22T00:00:00Z'),
        );
    }

    public function set(string $key, AccessToken $token, int $ttl): void
    {
    }

    public function delete(string $key): void
    {
    }
}
