<?php

namespace Didww\Tests\Support;

use Didww\Auth\MemoryTokenStore;
use Didww\Auth\TokenProvider;
use Didww\Configuration;
use Didww\Credentials;
use GuzzleHttp\Psr7\Response;

/**
 * Configures the SDK on a MockHttp stack with a fresh token store.
 */
trait ConfiguresMockHttp
{
    protected MockHttp $http;

    protected function configureMock(array $httpClientConfig = [], ?Credentials $credentials = null, ...$arguments): MockHttp
    {
        TokenProvider::forgetRateLimits();
        $this->http = new MockHttp();
        $arguments += ['tokenStore' => new MemoryTokenStore()];
        Configuration::configure($credentials ?? new Credentials('test-api-key', 'sandbox'), ['handler' => $this->http->stack] + $httpClientConfig, ...$arguments);

        return $this->http;
    }

    protected static function jsonApi(string $body = '{"data":[]}', int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/vnd.api+json'], $body);
    }

    protected static function unauthorized(): Response
    {
        return self::jsonApi('{"errors":[{"title":"Unauthorized","detail":"Authorization failed","code":"401","status":"401"}]}', 401);
    }
}
