<?php

namespace Didww\Tests\Auth;

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
        Configuration::configure(new Credentials('test-api-key', 'sandbox'), tokenStore: new FixedTokenStore(), baseUrl: $this->server->url);
        $sink = fopen('php://temp', 'w+b');

        $response = Configuration::getHttpClient()->request('GET', $this->server->url.'/v3/exports/1.csv.gz', ['sink' => $sink]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(2, $this->server->requests());
        $this->assertTrue(is_resource($sink));
        $this->assertSame('REALDATA', (string) $response->getBody());
        rewind($sink);
        $this->assertSame('REALDATA', stream_get_contents($sink));
    }
}
