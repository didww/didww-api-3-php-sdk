<?php

namespace Didww\Tests;

use Didww\Auth\Scope;
use Didww\Credentials;
use PHPUnit\Framework\Attributes\DataProvider;

class CredentialsTest extends \PHPUnit\Framework\TestCase
{
    public function testDefaultApiVersion()
    {
        $credentials = new Credentials('PLACEYOURAPIKEYHERE', 'sandbox');

        $this->assertSame('2026-10-22', $credentials->getVersion());
        $this->assertSame(Credentials::DEFAULT_API_VERSION, $credentials->getVersion());
        $this->assertNull($credentials->getScope());
    }

    public function testLaterApiVersion()
    {
        $this->assertSame('2099-01-01', (new Credentials('PLACEYOURAPIKEYHERE', 'sandbox', '2099-01-01'))->getVersion());
    }

    public function testEarlierApiVersionRejected()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('API version 2026-04-16 is not supported: this SDK requires 2026-10-22 or later; use didww/didww-api-3-php-sdk 10.x (branch release-10) for 2026-04-16');

        self::create('PLACEYOURAPIKEYHERE', 'sandbox', '2026-04-16');
    }

    public static function malformedVersionProvider(): array
    {
        return [['2026-10-22'."\n"], ['2026-1-22'], ['20261022'], ['latest'], ['']];
    }

    #[DataProvider('malformedVersionProvider')]
    public function testMalformedApiVersionRejected(string $version)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('API version must be a YYYY-MM-DD date');

        self::create('PLACEYOURAPIKEYHERE', 'sandbox', $version);
    }

    public function testScope()
    {
        $this->assertSame(Scope::READ, (new Credentials('k', 'sandbox', null, 'read'))->getScope());
        $this->assertSame(Scope::FULL, (new Credentials('k', 'sandbox', null, Scope::FULL))->getScope());
    }

    public function testUnknownScopeRejected()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Scope must be "read" or "full", got "admin"');

        self::create('k', 'sandbox', null, 'admin');
    }

    public static function unknownEnvironmentProvider(): array
    {
        return [['Production'], ['test'], ['']];
    }

    #[DataProvider('unknownEnvironmentProvider')]
    public function testUnknownEnvironmentRejected(string $env)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Environment must be "production" or "sandbox"');

        self::create('k', $env);
    }

    public function testEmptyApiKeyRejected()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('API key is required');

        self::create('', 'sandbox');
    }

    public static function invalidApiKeyProvider(): array
    {
        return [["key\n"], ["key\r"], ["k\x00ey"], ["key\x7F"], ['kéy']];
    }

    #[DataProvider('invalidApiKeyProvider')]
    public function testApiKeyWithForbiddenCharactersRejected(string $apiKey)
    {
        try {
            self::create($apiKey, 'sandbox');
            $this->fail('Expected an InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('API key contains characters not allowed in an HTTP header', $e->getMessage());
        }
    }

    public function testApiKeyWithTabAccepted()
    {
        $this->assertSame("key\tpart", (new Credentials("key\tpart", 'sandbox'))->getApiKey());
    }

    public function testEndpoints()
    {
        $production = new Credentials('k', 'production');
        $this->assertSame('https://api.didww.com/v3', $production->getEndpoint());
        $this->assertSame('https://auth.didww.com', $production->getAuthEndpoint());
        $this->assertSame('https://api-vin.didww.com/v3', $production->getVoiceInPricesEndpoint());
        $this->assertSame('https://api-vout.didww.com/v3', $production->getVoiceOutPricesEndpoint());
        $this->assertSame('https://api-sms.didww.com/v3', $production->getSmsPricesEndpoint());

        $sandbox = new Credentials('k', 'sandbox');
        $this->assertSame('https://sandbox-api.didww.com/v3', $sandbox->getEndpoint());
        $this->assertSame('https://auth-sandbox.didww.com', $sandbox->getAuthEndpoint());
        $this->assertSame('https://api-vin-sandbox.didww.com/v3', $sandbox->getVoiceInPricesEndpoint());
        $this->assertSame('https://api-vout-sandbox.didww.com/v3', $sandbox->getVoiceOutPricesEndpoint());
        $this->assertSame('https://api-sms-sandbox.didww.com/v3', $sandbox->getSmsPricesEndpoint());
    }

    public function testDebugOutputHidesApiKey()
    {
        $credentials = new Credentials('secret-api-key', 'sandbox', null, 'read');

        $output = print_r($credentials, true);

        $this->assertStringNotContainsString('secret-api-key', $output);
        $this->assertStringContainsString('[FILTERED]', $output);
        $this->assertStringContainsString('read', $output);
    }

    public function testApiKeyIsASensitiveParameter()
    {
        $this->assertNotEmpty((new \ReflectionParameter([Credentials::class, '__construct'], 'apiKey'))->getAttributes(\SensitiveParameter::class));
    }

    // Constructs Credentials for the tests that expect the constructor to throw.
    private static function create(mixed ...$arguments): Credentials
    {
        return new Credentials(...$arguments);
    }
}
