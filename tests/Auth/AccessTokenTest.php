<?php

namespace Didww\Tests\Auth;

use Didww\Auth\AccessToken;
use PHPUnit\Framework\TestCase;

class AccessTokenTest extends TestCase
{
    public function testFromFixture()
    {
        $token = AccessToken::fromArray(json_decode(file_get_contents(__DIR__.'/../fixtures/token/create.json'), true));

        $this->assertSame('test-access-token', $token->getAccessToken());
        $this->assertSame('Bearer', $token->getTokenType());
        $this->assertSame(900, $token->getExpiresIn());
        $this->assertSame(['api.didww.com'], $token->getAudience());
        $this->assertSame('full', $token->getScope());
        $this->assertSame('2026-10-22', $token->getApiVersion());
    }

    public function testArrayRoundTrip()
    {
        $token = $this->token();

        $copy = AccessToken::fromArray($token->toArray());

        $this->assertEquals($token, $copy);
        $this->assertSame('2026-10-22T10:00:00+00:00', $token->toArray()['minted_at']);
    }

    public function testFromArrayIsTolerant()
    {
        $token = AccessToken::fromArray(['aud' => 'api.didww.com', 'expires_in' => 'soon']);

        $this->assertSame('', $token->getAccessToken());
        $this->assertSame(['api.didww.com'], $token->getAudience());
        $this->assertSame(0, $token->getExpiresIn());
    }

    public function testDebugOutputHidesAccessToken()
    {
        $token = $this->token();

        $this->assertStringNotContainsString('secret-token-value', print_r($token, true));
        $this->assertStringNotContainsString('secret-token-value', (string) $token);
        ob_start();
        var_dump($token);
        $dump = ob_get_clean();
        $this->assertStringNotContainsString('secret-token-value', $dump);
        $this->assertStringContainsString('[FILTERED]', $dump);
        $this->assertStringContainsString('api.didww.com', (string) $token);
    }

    private function token(): AccessToken
    {
        return new AccessToken('secret-token-value', 'Bearer', 900, ['api.didww.com'], 'full', '2026-10-22', new \DateTimeImmutable('2026-10-22T10:00:00+00:00'));
    }
}
