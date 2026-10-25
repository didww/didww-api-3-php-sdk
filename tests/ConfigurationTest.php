<?php

namespace Didww\Tests;

use Didww\Auth\MemoryTokenStore;
use Didww\Auth\Scope;
use Didww\Auth\TokenProvider;
use Didww\Configuration;
use Didww\Credentials;
use Didww\Item\Did;
use Didww\Tests\Support\ArrayLogger;
use Didww\Tests\Support\ConfiguresMockHttp;
use Didww\Tests\Support\MockHttp;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    use ConfiguresMockHttp;

    protected function tearDown(): void
    {
        TokenProvider::forgetRateLimits();
    }

    public static function invalidUrlProvider(): array
    {
        $cases = [];
        $arguments = ['baseUrl' => 'API base', 'authUrl' => 'token service', 'voiceInPricesUrl' => 'Voice IN prices', 'voiceOutPricesUrl' => 'Voice OUT prices', 'smsPricesUrl' => 'SMS prices'];
        $urls = ['ftp://api.example.com/v3', 'https://', '/v3', 'api.example.com/v3', 'https://api.example.com/v3?x=1', 'https://api.example.com/v3?', 'https://api.example.com/v3#top', 'https://api.example.com/v3#', 'https://api example.com/v3'];
        foreach ($arguments as $argument => $name) {
            foreach ($urls as $url) {
                $cases[$argument.' '.$url] = [$argument, $name, $url];
            }
        }

        return $cases;
    }

    #[DataProvider('invalidUrlProvider')]
    public function testInvalidUrlRejected(string $argument, string $name, string $url)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('%s URL must be an http or https URL without a query or fragment, got "%s"', $name, $url));

        Configuration::configure(new Credentials('test-api-key', 'sandbox'), ...[$argument => $url]);
    }

    public function testUrlOverrides()
    {
        Configuration::configure(
            new Credentials('test-api-key', 'production'),
            baseUrl: 'HTTPS://proxy.example.com/',
            authUrl: 'http://auth.example.com:8080',
            voiceInPricesUrl: 'https://vin.example.com',
            voiceOutPricesUrl: 'https://vout.example.com/v3',
            smsPricesUrl: 'https://sms.example.com/v3/',
        );

        $this->assertSame('HTTPS://proxy.example.com/v3', Configuration::getBaseUri());
        $this->assertSame('http://auth.example.com:8080', Configuration::getAuthUri());
        $this->assertSame('https://vin.example.com', Configuration::getVoiceInPricesUri());
        $this->assertSame('https://vout.example.com/v3', Configuration::getVoiceOutPricesUri());
        $this->assertSame('https://sms.example.com/v3/', Configuration::getSmsPricesUri());
    }

    public function testEnvironmentDefaults()
    {
        Configuration::configure(new Credentials('test-api-key', 'sandbox'));

        $this->assertSame('https://sandbox-api.didww.com/v3', Configuration::getBaseUri());
        $this->assertSame('https://auth-sandbox.didww.com', Configuration::getAuthUri());
        $this->assertSame('https://api-vin-sandbox.didww.com/v3', Configuration::getVoiceInPricesUri());
        $this->assertSame('https://api-vout-sandbox.didww.com/v3', Configuration::getVoiceOutPricesUri());
        $this->assertSame('https://api-sms-sandbox.didww.com/v3', Configuration::getSmsPricesUri());
        $this->assertInstanceOf(\Psr\Log\NullLogger::class, Configuration::getLogger());
    }

    public function testBaseUrlOverrideReachesRequests()
    {
        $this->configureMock(baseUrl: 'https://proxy.example.com')->append(MockHttp::token(), self::jsonApi());

        Did::all();

        $this->assertSame('https://proxy.example.com/v3/dids', (string) $this->http->request(1)->getUri());
        $this->assertSame('https://auth-sandbox.didww.com/token', (string) $this->http->request(0)->getUri());
    }

    public function testHttpDefaultsReachRequests()
    {
        $this->configureMock()->append(MockHttp::token(), self::jsonApi());

        Did::all();

        foreach ([0, 1] as $i) {
            $options = $this->http->options($i);
            $this->assertFalse($options['allow_redirects']);
            $this->assertFalse($options['decode_content']);
            $this->assertFalse($options['cookies']);
            $this->assertSame(1, $options['curl'][CURLOPT_LOW_SPEED_LIMIT]);
            $this->assertSame(60, $options['curl'][CURLOPT_LOW_SPEED_TIME]);
            $this->assertMatchesRegularExpression('#\Adidww-php-sdk/#', $this->http->request($i)->getHeaderLine('User-Agent'));
        }
        $options = $this->http->options(1);
        $this->assertSame(10, $options['connect_timeout']);
        $this->assertSame(60, $options['read_timeout']);
        $this->assertArrayNotHasKey('timeout', $options);
    }

    public function testGuzzleClientOnlyOptionsAccepted()
    {
        Configuration::configure(new Credentials('test-api-key', 'sandbox'), ['max_total_connections' => 8, 'transport_sharing' => \GuzzleHttp\TransportSharing::HANDLER_PREFER]);

        $this->assertArrayNotHasKey('max_total_connections', Configuration::getHttpClient()->getConfig());
        $this->assertArrayNotHasKey('transport_sharing', Configuration::getHttpClient()->getConfig());
    }

    public function testGuzzleClientOnlyOptionsWithYourHandlerRejected()
    {
        $this->expectException(\InvalidArgumentException::class);

        Configuration::configure(new Credentials('test-api-key', 'sandbox'), ['handler' => \GuzzleHttp\HandlerStack::create(new \GuzzleHttp\Handler\MockHandler()), 'max_total_connections' => 8]);
    }

    public function testUserHttpConfigWins()
    {
        $this->configureMock([
            'connect_timeout' => 3,
            'timeout' => 20,
            'curl' => [CURLOPT_LOW_SPEED_TIME => 5],
            'headers' => ['user-agent' => 'my-app/1.0'],
        ])->append(MockHttp::token(), self::jsonApi());

        Configuration::getHttpClient()->request('GET', 'https://sandbox-api.didww.com/v3/dids');

        $options = $this->http->options(1);
        $this->assertSame(3, $options['connect_timeout']);
        $this->assertSame(20, $options['timeout']);
        $this->assertSame(5, $options['curl'][CURLOPT_LOW_SPEED_TIME]);
        $this->assertSame(1, $options['curl'][CURLOPT_LOW_SPEED_LIMIT]);
        $this->assertSame('my-app/1.0', $this->http->request(1)->getHeaderLine('User-Agent'));
        $this->assertSame(10, $this->http->options(0)['timeout'], 'the mint keeps its own timeout');
    }

    public function testUserAgentReachesJsonApiRequests()
    {
        $this->configureMock(['headers' => ['User-Agent' => 'my-app/1.0']])->append(MockHttp::token(), self::jsonApi());

        Did::all();

        $this->assertSame('my-app/1.0', $this->http->request(0)->getHeaderLine('User-Agent'));
        $this->assertSame('my-app/1.0', $this->http->request(1)->getHeaderLine('User-Agent'));
    }

    public function testApiVersionAndScopeOverrides()
    {
        $this->configureMock([], null, apiVersion: '2099-01-01', scope: 'read')->append(MockHttp::token(scope: 'read', apiVersion: '2099-01-01'), self::jsonApi());

        Did::all();

        $this->assertSame('2099-01-01', Configuration::getCredentials()->getVersion());
        $this->assertSame(Scope::READ, Configuration::getCredentials()->getScope());
        $this->assertJsonStringEqualsJsonString('{"aud":["api.didww.com"],"api_version":"2099-01-01","scope":"read"}', (string) $this->http->request(0)->getBody());
    }

    public function testEarlierApiVersionOverrideRejected()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not supported');

        Configuration::configure(new Credentials('test-api-key', 'sandbox'), apiVersion: '2026-04-16');
    }

    public function testDefaultTokenStoreIsSharedByTheProcess()
    {
        Configuration::configure(new Credentials('test-api-key', 'sandbox'));
        $first = Configuration::getTokenStore();
        Configuration::configure(new Credentials('other-api-key', 'production'));

        $this->assertInstanceOf(MemoryTokenStore::class, $first);
        $this->assertSame($first, Configuration::getTokenStore());

        $store = new MemoryTokenStore();
        Configuration::configure(new Credentials('test-api-key', 'sandbox'), tokenStore: $store);
        $this->assertSame($store, Configuration::getTokenStore());
    }

    public function testLoggerReceivesTokenWarnings()
    {
        $logger = new ArrayLogger();
        $this->configureMock([], null, logger: $logger)->append(MockHttp::token(apiVersion: '2099-01-01'), self::jsonApi());

        Did::all();

        $this->assertSame(['DIDWW token api_version mismatch: requested 2026-10-22, granted 2099-01-01'], $logger->messages('warning'));
    }

    public function testRawHttpClientSendsNoToken()
    {
        $this->configureMock()->append(new Response(200));

        Configuration::getRawHttpClient()->request('GET', 'https://api-vin-sandbox.didww.com/v3/prices.csv.gz');

        $this->assertSame(1, $this->http->count());
        $this->assertFalse($this->http->request(0)->hasHeader('Authorization'));
    }

    public function testRawHttpClientNeverRedirects()
    {
        $this->configureMock(['allow_redirects' => true])->append(new Response(302, ['Location' => 'https://elsewhere.example.com/']), new Response(200));

        $response = Configuration::getRawHttpClient()->request('GET', 'https://api-vin-sandbox.didww.com/v3/prices.csv.gz');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(1, $this->http->count());
    }

    public function testFailedConfigureKeepsPreviousConfiguration()
    {
        Configuration::configure(new Credentials('test-api-key', 'sandbox'));

        try {
            Configuration::configure(new Credentials('test-api-key', 'production'), authUrl: 'ftp://auth.example.com');
        } catch (\InvalidArgumentException) {
        }

        $this->assertSame('https://sandbox-api.didww.com/v3', Configuration::getBaseUri());
        $this->assertSame('sandbox', Configuration::getCredentials()->getEnv());
    }
}
