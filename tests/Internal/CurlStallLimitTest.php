<?php

namespace Didww\Tests\Internal;

use Didww\Configuration;
use Didww\Credentials;
use Didww\Internal\CurlStallLimit;
use Didww\Tests\Prices\Support\ScriptedServer;
use Didww\Tests\Support\FixedTokenStore;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Handler\Proxy;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use VCR\VCR;

class CurlStallLimitTest extends TestCase
{
    private ?ScriptedServer $server = null;

    protected function tearDown(): void
    {
        $this->server?->stop();
    }

    public function testCurlOptionsMergedUnderTheCallers()
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200)]));
        $stack->push(Middleware::history($history));

        CurlStallLimit::wrap($stack)(new Request('GET', 'https://api.example.com/'), ['curl' => [CURLOPT_LOW_SPEED_TIME => 5]])->wait();

        $this->assertSame([CURLOPT_LOW_SPEED_TIME => 5, CURLOPT_LOW_SPEED_LIMIT => 1], $history[0]['options']['curl']);
    }

    public function testDefaultBranchOfGuzzlesProxyGetsTheStallLimit()
    {
        $history = [];
        $stack = new HandlerStack(Proxy::wrapStreaming(new MockHandler([new Response(200)]), new StreamHandler()));
        $stack->push(Middleware::history($history));
        $handler = CurlStallLimit::wrap($stack);

        $handler(new Request('GET', 'https://api.example.com/'), [])->wait();

        $this->assertSame(CurlStallLimit::OPTIONS, $history[0]['options']['curl']);
    }

    public static function streamHandlerProvider(): array
    {
        return [
            'stream handler' => [static fn () => ['handler' => new StreamHandler()]],
            'stream handler in a stack' => [static fn () => ['handler' => HandlerStack::create(new StreamHandler())]],
            'streamed request of the default handler' => [static fn () => []],
        ];
    }

    #[DataProvider('streamHandlerProvider')]
    #[Group('wire')]
    #[RequiresPhpExtension('curl')]
    public function testStreamHandlerGetsNone(callable $httpClientConfig)
    {
        VCR::turnOff();
        try {
            $this->server = new ScriptedServer([[['send' => "HTTP/1.1 200 OK\r\nContent-Length: 2\r\nConnection: close\r\n\r\nok"]]]);
            Configuration::configure(new Credentials('test-api-key', 'sandbox'), $httpClientConfig(), tokenStore: new FixedTokenStore(), baseUrl: $this->server->url);

            $response = Configuration::getHttpClient()->request('GET', $this->server->url.'/v3/dids', ['stream' => true]);

            $this->assertSame('ok', (string) $response->getBody());
        } finally {
            VCR::turnOn();
        }
    }
}
