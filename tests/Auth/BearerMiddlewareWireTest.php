<?php

namespace Didww\Tests\Auth;

use Didww\Auth\AccessToken;
use Didww\Auth\Audience;
use Didww\Auth\TokenStore;
use Didww\Configuration;
use Didww\Credentials;
use Didww\Tests\Prices\Support\ScriptedServer;
use Didww\Tests\Support\FixedTokenStore;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use VCR\VCR;

/**
 * The 401 retry over real curl against a scripted local server.
 */
#[Group('wire')]
#[RequiresPhpExtension('curl')]
class BearerMiddlewareWireTest extends TestCase
{
    private ?ScriptedServer $server = null;

    protected function setUp(): void
    {
        VCR::turnOff();
    }

    protected function tearDown(): void
    {
        $this->server?->stop();
        VCR::turnOn();
    }

    public function testResourceSinkStaysOpenAfterRetry()
    {
        $this->server = new ScriptedServer([
            [['send' => "HTTP/1.1 401 Unauthorized\r\nContent-Type: application/vnd.api+json\r\nContent-Length: 13\r\nConnection: close\r\n\r\n{\"errors\":[]}"]],
            [['send' => "HTTP/1.1 200 OK\r\nContent-Length: 8\r\nConnection: close\r\n\r\nREALDATA"]],
        ]);
        Configuration::configure(new Credentials('test-api-key', 'sandbox'), tokenStore: self::rotatingStore(), baseUrl: $this->server->url);
        $sink = fopen('php://temp', 'w+b');

        $response = Configuration::getHttpClient()->request('GET', $this->server->url.'/v3/exports/1.csv.gz', ['sink' => $sink]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(2, $this->server->requests());
        $this->assertStringContainsString("\r\nAuthorization: Bearer ".FixedTokenStore::ACCESS_TOKEN."-2\r\n", $this->server->requests()[1]);
        $this->assertTrue(is_resource($sink));
        $this->assertSame('REALDATA', (string) $response->getBody());
        rewind($sink);
        $this->assertSame('REALDATA', stream_get_contents($sink));
    }

    // A store that holds a fixed token and a second one once the first is
    // refused, so that the retry needs no token service.
    private static function rotatingStore(): TokenStore
    {
        return new class implements TokenStore {
            private string $accessToken = FixedTokenStore::ACCESS_TOKEN;

            public function get(string $key): ?AccessToken
            {
                return new AccessToken($this->accessToken, 'Bearer', 900, [Audience::API], 'full', '2026-10-22', new \DateTimeImmutable('2026-10-22T00:00:00Z'));
            }

            public function set(string $key, AccessToken $token, int $ttl): void
            {
                $this->accessToken = $token->getAccessToken();
            }

            public function delete(string $key): void
            {
                $this->accessToken = FixedTokenStore::ACCESS_TOKEN.'-2';
            }
        };
    }
}
