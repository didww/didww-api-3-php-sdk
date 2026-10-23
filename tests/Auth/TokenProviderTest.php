<?php

namespace Didww\Tests\Auth;

use Didww\Auth\MemoryTokenStore;
use Didww\Auth\Scope;
use Didww\Auth\TokenProvider;
use Didww\Auth\TokenStore;
use Didww\Credentials;
use Didww\Exception\AuthErrorKind;
use Didww\Exception\DidwwAuthException;
use Didww\Tests\Support\ArrayLogger;
use Didww\Tests\Support\FailingTokenStore;
use Didww\Tests\Support\MockHttp;
use Didww\Tests\Support\RecordingTokenStore;
use Didww\Tests\Support\TraceArgs;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ConnectTimeoutException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ResponseTransferException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class TokenProviderTest extends TestCase
{
    private const AUTH_URL = 'https://auth.example.com';
    private const API_KEY = 'test-api-key';

    private MockHttp $http;
    private ArrayLogger $logger;
    private array $sleeps = [];
    private float $random = 1.0;
    private float $now = 1000.0;

    protected function setUp(): void
    {
        TokenProvider::forgetRateLimits();
        $this->http = new MockHttp();
        $this->logger = new ArrayLogger();
    }

    protected function tearDown(): void
    {
        TokenProvider::forgetRateLimits();
    }

    public function testMintRequest()
    {
        $this->http->append(MockHttp::tokenError(200, 'create.json'));

        $token = $this->provider()->token('api.didww.com');

        $this->assertSame('test-access-token', $token->getAccessToken());
        $this->assertSame('Bearer', $token->getTokenType());
        $this->assertSame(900, $token->getExpiresIn());
        $this->assertSame(['api.didww.com'], $token->getAudience());
        $this->assertSame('full', $token->getScope());
        $this->assertSame('2026-10-22', $token->getApiVersion());

        $this->assertSame(1, $this->http->count());
        $request = $this->http->request(0);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://auth.example.com/token', (string) $request->getUri());
        $this->assertJsonStringEqualsJsonFile(__DIR__.'/../fixtures/token/create_request.json', (string) $request->getBody());
        $this->assertSame(self::API_KEY, $request->getHeaderLine('Api-Key'));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertFalse($request->hasHeader('Authorization'));

        $options = $this->http->options(0);
        $this->assertSame(10, $options['timeout']);
        $this->assertSame(10, $options['connect_timeout']);
        $this->assertFalse($options['allow_redirects']);
        $this->assertFalse($options['http_errors']);
        $this->assertFalse($options['decode_content']);
        $this->assertFalse($options['stream']);
    }

    public function testMintRequestNotStreamedWhenClientStreams()
    {
        $this->http->append(MockHttp::tokenError(200, 'create.json'));

        $this->provider(httpConfig: ['stream' => true])->token('api.didww.com');

        $this->assertFalse($this->http->options(0)['stream']);
    }

    public function testMintRequestWithScope()
    {
        $this->http->append(MockHttp::token(scope: 'read'));

        $this->provider(new Credentials(self::API_KEY, 'sandbox', null, Scope::READ))->token('api.didww.com');

        $this->assertJsonStringEqualsJsonFile(__DIR__.'/../fixtures/token/create_request_read_scope.json', (string) $this->http->request(0)->getBody());
    }

    public function testMintRequestWithApiVersion()
    {
        $this->http->append(MockHttp::token(apiVersion: '2099-01-01'));

        $token = $this->provider(new Credentials(self::API_KEY, 'sandbox', '2099-01-01'))->token('api.didww.com');

        $this->assertSame('2099-01-01', $token->getApiVersion());
        $this->assertJsonStringEqualsJsonString('{"aud":["api.didww.com"],"api_version":"2099-01-01"}', (string) $this->http->request(0)->getBody());
    }

    public function testTokenReused()
    {
        $this->http->append(MockHttp::token('token-1'));
        $provider = $this->provider();

        $first = $provider->token('api.didww.com');
        $second = $provider->token('api.didww.com');

        $this->assertSame($first->getAccessToken(), $second->getAccessToken());
        $this->assertSame(1, $this->http->count());
    }

    public function testTokenMintedPerAudience()
    {
        $this->http->append(MockHttp::token('token-1'), MockHttp::token('token-2', ['api-vin.didww.com']));
        $provider = $this->provider();

        $api = $provider->token('api.didww.com');
        $vin = $provider->token('api-vin.didww.com');

        $this->assertNotSame($api->getAccessToken(), $vin->getAccessToken());
        $this->assertSame(2, $this->http->count());
        $this->assertJsonStringEqualsJsonString('{"aud":["api.didww.com"],"api_version":"2026-10-22"}', (string) $this->http->request(0)->getBody());
        $this->assertJsonStringEqualsJsonString('{"aud":["api-vin.didww.com"],"api_version":"2026-10-22"}', (string) $this->http->request(1)->getBody());
    }

    public function testTokenSharedThroughStore()
    {
        $this->http->append(MockHttp::token('token-1'));
        $store = new MemoryTokenStore();

        $this->provider(store: $store)->token('api.didww.com');
        $token = $this->provider(store: $store)->token('api.didww.com');

        $this->assertSame('token-1', $token->getAccessToken());
        $this->assertSame(1, $this->http->count());
    }

    public function testTokenCachedForGrantedLifetime()
    {
        $this->http->append(MockHttp::token(expiresIn: 300));
        $store = new RecordingTokenStore();

        $this->provider(store: $store)->token('api.didww.com');

        $this->assertCount(1, $store->sets);
        $this->assertStringStartsWith('didww:token:v1:', $store->sets[0][0]);
        $this->assertSame(300, $store->sets[0][1]);
    }

    public function testCacheKey()
    {
        $key = fn (string $apiKey = 'key-1', ?string $version = null, ?Scope $scope = null, string $authUrl = 'https://auth.example.com', string $audience = 'api.didww.com') => (new TokenProvider($this->guzzle(), $authUrl, new Credentials($apiKey, 'sandbox', $version, $scope), new MemoryTokenStore(), $this->logger))->cacheKey($audience);
        $base = $key();

        $this->assertSame('didww:token:v1:2e1c5435f0e67f91d34497fabf69840a945923a47982fad172c353b3f26be539', $base);
        $this->assertSame($base, $key(authUrl: 'https://auth.example.com/'));
        $this->assertNotSame($base, $key(audience: 'api-sms.didww.com'));
        $this->assertNotSame($base, $key(version: '2099-01-01'));
        $this->assertNotSame($base, $key(scope: Scope::READ));
        $this->assertNotSame($base, $key(authUrl: 'https://auth-sandbox.example.com'));
        $this->assertNotSame($base, $key(apiKey: 'key-2'));
    }

    public function testInvalidClient()
    {
        $this->http->append(MockHttp::tokenError(401, 'invalid_client.json'));

        $e = $this->mintFailure();

        $this->assertSame(AuthErrorKind::INVALID_CLIENT, $e->getKind());
        $this->assertSame(401, $e->getStatusCode());
        $this->assertSame('invalid_client', $e->getError());
        $this->assertSame('Client authentication failed.', $e->getErrorDescription());
        $this->assertSame('Token service: invalid_client: Client authentication failed.', $e->getMessage());
        $this->assertSame(1, $this->http->count());
        $this->assertSame([], $this->sleeps);
    }

    public function testInvalidRequest()
    {
        $this->http->append(MockHttp::tokenError(400, 'invalid_scope.json'));

        $e = $this->mintFailure();

        $this->assertSame(AuthErrorKind::INVALID_REQUEST, $e->getKind());
        $this->assertSame(400, $e->getStatusCode());
        $this->assertSame('invalid_scope', $e->getError());
        $this->assertSame(1, $this->http->count());
    }

    public static function rateLimitProvider(): array
    {
        return [
            'jitter 0' => [0.0, 30.0],
            'jitter 1' => [1.0, 36.0],
        ];
    }

    #[DataProvider('rateLimitProvider')]
    public function testRateLimited(float $jitter, float $window)
    {
        $this->random = $jitter;
        $this->http->append(MockHttp::tokenError(429, 'rate_limit_exceeded.json', ['Retry-After' => '30']), MockHttp::token('token-2'));
        $provider = $this->provider();

        $e = $this->mintFailure($provider);
        $this->assertSame(AuthErrorKind::RATE_LIMITED, $e->getKind());
        $this->assertSame(429, $e->getStatusCode());
        $this->assertSame(30, $e->getRetryAfter(), 'the server value, without the spread');

        $this->now += $window - 0.5;
        $e = $this->mintFailure($provider);
        $this->assertSame(AuthErrorKind::RATE_LIMITED, $e->getKind());
        $this->assertSame('rate_limit_exceeded', $e->getError());
        $this->assertSame(1, $e->getRetryAfter());

        $e = $this->mintFailure($provider, 'api-vin.didww.com');
        $this->assertSame(AuthErrorKind::RATE_LIMITED, $e->getKind());
        $this->assertSame(1, $this->http->count());

        $this->now += 0.5;
        $this->assertSame('token-2', $provider->token('api.didww.com')->getAccessToken());
        $this->assertSame(2, $this->http->count());
        $this->assertSame([], $this->sleeps);
    }

    public static function shortRetryAfterProvider(): array
    {
        return [
            'zero, jitter 0' => ['0', 0.0, 1.0],
            'zero, jitter 1' => ['0', 1.0, 2.0],
            'missing' => [null, 0.0, 1.0],
            'HTTP date' => ['Wed, 21 Oct 2026 07:28:00 GMT', 0.0, 1.0],
        ];
    }

    #[DataProvider('shortRetryAfterProvider')]
    public function testRateLimitedWithShortOrUnusableRetryAfter(?string $retryAfter, float $jitter, float $window)
    {
        $this->random = $jitter;
        $this->http->append(MockHttp::tokenError(429, 'rate_limit_exceeded.json', null === $retryAfter ? [] : ['Retry-After' => $retryAfter]), MockHttp::token('token-ok'));
        $provider = $this->provider();

        $e = $this->mintFailure($provider);
        $this->assertSame(1, $e->getRetryAfter());

        $this->now += $window - 0.5;
        $this->assertSame(AuthErrorKind::RATE_LIMITED, $this->mintFailure($provider)->getKind());

        $this->now += 0.5;
        $this->assertSame('token-ok', $provider->token('api.didww.com')->getAccessToken());
    }

    public static function backoffProvider(): array
    {
        return [
            'jitter 1' => [1.0, [1.0, 2.0, 4.0]],
            'jitter 0.5' => [0.5, [0.5, 1.0, 2.0]],
            'jitter 0' => [0.0, [0.0, 0.0, 0.0]],
        ];
    }

    #[DataProvider('backoffProvider')]
    public function testServerErrorRetriedWithBackoff(float $jitter, array $sleeps)
    {
        $this->random = $jitter;
        $this->http->append(
            MockHttp::tokenError(503, 'server_error.json'),
            MockHttp::tokenError(503, 'server_error.json'),
            MockHttp::tokenError(503, 'server_error.json'),
            MockHttp::token('token-ok'),
        );

        $token = $this->provider()->token('api.didww.com');

        $this->assertSame('token-ok', $token->getAccessToken());
        $this->assertSame(4, $this->http->count());
        $this->assertSame($sleeps, $this->sleeps);
    }

    public function testServerErrorExhausted()
    {
        for ($i = 0; $i < 3; ++$i) {
            $this->http->append(new Response(503));
        }
        $this->http->append(MockHttp::tokenError(500, 'server_error.json'));

        $e = $this->mintFailure();

        $this->assertSame(AuthErrorKind::SERVER_ERROR, $e->getKind());
        $this->assertSame(500, $e->getStatusCode());
        $this->assertSame('server_error', $e->getError());
        $this->assertSame(TokenProvider::MAX_MINT_ATTEMPTS, $this->http->count());
        $this->assertCount(TokenProvider::MAX_MINT_ATTEMPTS - 1, $this->sleeps);
    }

    public function testServerErrorWithoutJsonBody()
    {
        for ($i = 0; $i < 4; ++$i) {
            $this->http->append(new Response(502, [], '<html>Bad Gateway</html>'));
        }

        $e = $this->mintFailure();

        $this->assertSame(AuthErrorKind::SERVER_ERROR, $e->getKind());
        $this->assertSame(502, $e->getStatusCode());
        $this->assertNull($e->getError());
        $this->assertSame('Token service responded with status 502', $e->getMessage());
    }

    public function testUnexpectedStatus()
    {
        $this->http->append(new Response(403));

        $e = $this->mintFailure();

        $this->assertSame(AuthErrorKind::UNEXPECTED_STATUS, $e->getKind());
        $this->assertSame(403, $e->getStatusCode());
        $this->assertSame(1, $this->http->count());
        $this->assertSame([], $this->sleeps);
    }

    public function testRedirectNotFollowed()
    {
        $this->http->append(new Response(302, ['Location' => 'https://elsewhere.example.com/token']));

        $e = $this->mintFailure();

        $this->assertSame(AuthErrorKind::UNEXPECTED_STATUS, $e->getKind());
        $this->assertSame(302, $e->getStatusCode());
        $this->assertSame(1, $this->http->count());
    }

    public function testLaterCallMintsAgainAfterFailure()
    {
        $this->http->append(MockHttp::tokenError(401, 'invalid_client.json'), MockHttp::token('token-ok'));
        $provider = $this->provider();

        $this->mintFailure($provider);

        $this->assertSame('token-ok', $provider->token('api.didww.com')->getAccessToken());
    }

    public function testConnectionErrorRetried()
    {
        $request = new Request('POST', self::AUTH_URL.'/token');
        for ($i = 0; $i < 4; ++$i) {
            $this->http->append(new ConnectException('cURL error 7: Failed to connect', $request));
        }

        $e = $this->mintFailure();

        $this->assertSame(AuthErrorKind::CONNECTION_ERROR, $e->getKind());
        $this->assertNull($e->getStatusCode());
        $this->assertInstanceOf(ConnectException::class, $e->getPrevious());
        $this->assertSame('Token service: cURL error 7: Failed to connect', $e->getMessage());
        $this->assertSame(4, $this->http->count());
        $this->assertCount(3, $this->sleeps);
    }

    public function testTimeoutRetried()
    {
        $request = new Request('POST', self::AUTH_URL.'/token');
        $this->http->append(new ConnectTimeoutException('cURL error 28: SSL connection timeout', $request), MockHttp::token('token-ok'));

        $this->assertSame('token-ok', $this->provider()->token('api.didww.com')->getAccessToken());
        $this->assertSame(2, $this->http->count());
        $this->assertCount(1, $this->sleeps);
    }

    public static function tlsErrorProvider(): array
    {
        $request = new Request('POST', self::AUTH_URL.'/token');

        return [
            'SSL connect error' => [new ConnectException('cURL error 35: TLS alert', $request)],
            'certificate verification' => [new ConnectException('cURL error 60: SSL certificate problem', $request)],
            'other curl error' => [new RequestException('cURL error 58: Problem with the local client certificate', $request)],
            'without errno' => [new ConnectException('SSL operation failed: certificate verify failed', $request)],
        ];
    }

    #[DataProvider('tlsErrorProvider')]
    public function testTlsErrorNotRetried(\Exception $failure)
    {
        $this->http->append($failure, MockHttp::token());

        $e = $this->mintFailure();

        $this->assertSame(AuthErrorKind::CONNECTION_ERROR, $e->getKind());
        $this->assertSame($failure, $e->getPrevious());
        $this->assertSame(1, $this->http->count());
        $this->assertSame([], $this->sleeps);
    }

    public static function failureWithRequestProvider(): array
    {
        return [
            'connect error' => [static fn (RequestInterface $request) => new ConnectException('cURL error 60: SSL certificate problem', $request)],
            'request error chaining a connect error' => [static fn (RequestInterface $request) => new RequestException('cURL error 60: SSL certificate problem', $request, 0, new ConnectException('cURL error 60', $request))],
        ];
    }

    #[DataProvider('failureWithRequestProvider')]
    public function testConnectionErrorChainCarriesNoApiKey(callable $failure)
    {
        $this->http->append($failure);

        $e = TraceArgs::keepingArgs(fn () => $this->mintFailure());

        $this->assertSame(self::API_KEY, $this->http->request(0)->getHeaderLine('Api-Key'));
        $this->assertSame(AuthErrorKind::CONNECTION_ERROR, $e->getKind());
        $this->assertSame('cURL error 60: SSL certificate problem', $e->getPrevious()->getMessage());
        $this->assertSame($this->http->history[0]['error']::class, $e->getPrevious()::class);
        $requests = 0;
        for ($previous = $e->getPrevious(); null !== $previous; $previous = $previous->getPrevious()) {
            $this->assertFalse($previous->getRequest()->hasHeader('Api-Key'));
            $this->assertSame(self::AUTH_URL.'/token', (string) $previous->getRequest()->getUri());
            ++$requests;
        }
        $this->assertGreaterThan(0, $requests);
        // The unredacted exception is not kept in the trace either.
        $traceArgs = TraceArgs::ofSdkFrames($e);
        $this->assertStringContainsString('GuzzleExceptions::withoutHeader', $traceArgs);
        $this->assertStringNotContainsString(self::API_KEY, $traceArgs);
    }

    public function testOtherErrorDuringMintKeepsNoApiKey()
    {
        $this->http->append(static function () {
            throw new \InvalidArgumentException('SSL CA bundle not found: /nonexistent/ca.pem');
        });

        $e = TraceArgs::keepingArgs(fn () => $this->mintFailure());

        $this->assertSame(AuthErrorKind::CONNECTION_ERROR, $e->getKind());
        $this->assertSame('InvalidArgumentException: SSL CA bundle not found: /nonexistent/ca.pem', $e->getPrevious()->getMessage());
        $this->assertNull($e->getPrevious()->getPrevious());
        $this->assertStringNotContainsString(self::API_KEY, TraceArgs::ofSdkFrames($e));
        $this->assertStringNotContainsString(self::API_KEY, TraceArgs::ofSdkFrames($e, 'GuzzleHttp\\'));
    }

    public function testCutTokenAnswerKeepsNoToken()
    {
        $failure = static fn (RequestInterface $request) => new ResponseTransferException('cURL error 18: end of response with 500 bytes missing', $request, new Response(200, [], '{"access_token":"secret-token"}'));
        $this->http->append($failure, $failure, $failure, $failure);

        $e = $this->mintFailure();

        $this->assertSame(AuthErrorKind::CONNECTION_ERROR, $e->getKind());
        $previous = $e->getPrevious();
        $this->assertInstanceOf(ResponseTransferException::class, $previous);
        $this->assertSame(200, $previous->getResponse()->getStatusCode());
        $this->assertSame('', (string) $previous->getResponse()->getBody());
        $this->assertFalse($previous->getRequest()->hasHeader('Api-Key'));
    }

    public function testRetryStopsInsideRateLimitWindow()
    {
        $this->http->append(new Response(503));
        $other = new MockHttp([MockHttp::tokenError(429, 'rate_limit_exceeded.json', ['Retry-After' => '30'])]);
        $otherProvider = new TokenProvider(new \GuzzleHttp\Client(['handler' => $other->stack]), self::AUTH_URL, new Credentials(self::API_KEY, 'sandbox'), new MemoryTokenStore(), $this->logger, null, fn () => 0.0, fn () => $this->now);
        $provider = $this->provider(sleep: function () use ($otherProvider) {
            // Another audience's mint is answered 429 while this one backs off.
            try {
                $otherProvider->token('api-sms.didww.com');
            } catch (DidwwAuthException) {
            }
        });

        $e = $this->mintFailure($provider);

        $this->assertSame(AuthErrorKind::RATE_LIMITED, $e->getKind());
        $this->assertSame(1, $this->http->count());
    }

    public static function invalidGrantProvider(): array
    {
        return [
            'audience mismatch' => [MockHttp::token('token-1', ['api-sms.didww.com']), 'granted audience [api-sms.didww.com], requested api.didww.com'],
            'empty token' => [MockHttp::token(''), 'granted audience [api.didww.com], requested api.didww.com'],
            'not JSON' => [new Response(200, [], 'not json'), 'granted audience [], requested api.didww.com'],
        ];
    }

    #[DataProvider('invalidGrantProvider')]
    public function testInvalidGrant(Response $response, string $description)
    {
        $this->http->append($response);
        $store = new MemoryTokenStore();

        $e = TraceArgs::keepingArgs(fn () => $this->mintFailure($this->provider(store: $store)));

        $this->assertSame(AuthErrorKind::INVALID_GRANT, $e->getKind());
        $this->assertSame(200, $e->getStatusCode());
        $this->assertSame($description, $e->getErrorDescription());
        $this->assertSame(['entries' => 0], $store->__debugInfo());
        $this->assertStringNotContainsString('token-1', TraceArgs::ofSdkFrames($e));
    }

    public function testScopeMismatchWarning()
    {
        $this->http->append(MockHttp::token(scope: 'full'));

        $this->provider(new Credentials(self::API_KEY, 'sandbox', null, 'read'))->token('api.didww.com');

        $this->assertSame(['DIDWW token scope mismatch: requested read, granted full'], $this->logger->messages('warning'));
        $this->assertSame(['requested' => 'read', 'granted' => 'full'], $this->logger->records[0]['context']);
    }

    public function testApiVersionMismatchWarning()
    {
        $this->http->append(MockHttp::token(apiVersion: '2099-01-01'));

        $this->provider()->token('api.didww.com');

        $this->assertSame(['DIDWW token api_version mismatch: requested 2026-10-22, granted 2099-01-01'], $this->logger->messages('warning'));
    }

    public function testNoWarningWhenGrantMatches()
    {
        $this->http->append(MockHttp::token(scope: 'read'));

        $this->provider(new Credentials(self::API_KEY, 'sandbox', null, Scope::READ))->token('api.didww.com');

        $this->assertSame([], $this->logger->messages('warning'));
    }

    public function testNoScopeWarningWithoutRequestedScope()
    {
        $this->http->append(MockHttp::token(scope: 'full'));

        $this->provider()->token('api.didww.com');

        $this->assertSame([], $this->logger->messages('warning'));
    }

    public function testMintedDebugLog()
    {
        $this->http->append(MockHttp::token());

        $this->provider()->token('api.didww.com');

        $this->assertSame(['DIDWW token minted'], $this->logger->messages('debug'));
        $context = $this->logger->records[0]['context'];
        $this->assertSame(['api.didww.com'], $context['aud']);
        $this->assertSame('full', $context['scope']);
        $this->assertSame('2026-10-22', $context['api_version']);
        $this->assertSame(900, $context['expires_in']);
        $this->assertArrayHasKey('duration_ms', $context);
    }

    public function testFailingLoggerDoesNotFailMint()
    {
        $this->http->append(MockHttp::token());
        $logger = new class extends \Psr\Log\AbstractLogger {
            public function log($level, \Stringable|string $message, array $context = []): void
            {
                throw new \RuntimeException('logger down');
            }
        };
        $provider = new TokenProvider($this->guzzle(), self::AUTH_URL, new Credentials(self::API_KEY, 'sandbox'), new MemoryTokenStore(), $logger);

        $this->assertSame('token-1', $provider->token('api.didww.com')->getAccessToken());
    }

    public function testInvalidateEvictsTheRefusedToken()
    {
        $this->http->append(MockHttp::token('token-1'), MockHttp::token('token-2'));
        $provider = $this->provider();
        $provider->token('api.didww.com');

        $provider->invalidate('api.didww.com', 'token-1');

        $this->assertSame('token-2', $provider->token('api.didww.com')->getAccessToken());
    }

    public function testInvalidateKeepsATokenMintedAfterTheRefusedOne()
    {
        $this->http->append(MockHttp::token('token-1'));
        $provider = $this->provider();
        $provider->token('api.didww.com');

        $provider->invalidate('api.didww.com', 'stale-token');

        $this->assertSame('token-1', $provider->token('api.didww.com')->getAccessToken());
        $this->assertSame(1, $this->http->count());
    }

    public function testStoreFailuresAreLoggedNotFatal()
    {
        $this->http->append(MockHttp::token('token-1'), MockHttp::token('token-2'));
        $provider = $this->provider(store: new FailingTokenStore());

        $this->assertSame('token-1', $provider->token('api.didww.com')->getAccessToken());
        $provider->invalidate('api.didww.com', 'token-1');
        $this->assertSame('token-2', $provider->token('api.didww.com')->getAccessToken());

        $this->assertSame([
            'DIDWW token store read failed',
            'DIDWW token store write failed',
            'DIDWW token store read failed',
            'DIDWW token store read failed',
            'DIDWW token store write failed',
        ], $this->logger->messages('warning'));
        $this->assertInstanceOf(\RuntimeException::class, $this->logger->records[0]['context']['exception']);
    }

    public function testStoreDeleteFailureIsLogged()
    {
        $this->http->append(MockHttp::token('token-1'));
        $store = new FailingTokenStore(failGet: false, failSet: false);
        $provider = $this->provider(store: $store);
        $provider->token('api.didww.com');

        $provider->invalidate('api.didww.com', 'token-1');

        $this->assertSame(['DIDWW token store delete failed'], $this->logger->messages('warning'));
    }

    public function testErrorsAndLogsNeverContainTheKeyOrToken()
    {
        $this->http->append(
            MockHttp::token('secret-token-value', apiVersion: '2099-01-01', scope: 'full'),
            MockHttp::tokenError(401, 'invalid_client.json'),
        );
        $provider = $this->provider(new Credentials(self::API_KEY, 'sandbox', null, 'read'), new FailingTokenStore(failGet: false, failSet: true));
        $provider->token('api.didww.com');
        $e = $this->mintFailure($provider, 'api-sms.didww.com');

        $output = $e->getMessage();
        foreach ($this->logger->records as $record) {
            $context = $record['context'];
            if (isset($context['exception'])) {
                $context['exception'] = $context['exception']->getMessage();
            }
            $output .= $record['message'].json_encode($context);
        }
        $this->assertStringNotContainsString(self::API_KEY, $output);
        $this->assertStringNotContainsString('secret-token-value', $output);
    }

    public function testSensitiveParameters()
    {
        $this->assertNotEmpty((new \ReflectionParameter([TokenProvider::class, 'invalidate'], 'refusedAccessToken'))->getAttributes(\SensitiveParameter::class));
        $this->assertNotEmpty((new \ReflectionParameter([TokenProvider::class, 'verifyGrant'], 'token'))->getAttributes(\SensitiveParameter::class));
    }

    private function provider(?Credentials $credentials = null, ?TokenStore $store = null, ?callable $sleep = null, array $httpConfig = []): TokenProvider
    {
        return new TokenProvider(
            $this->guzzle($httpConfig),
            self::AUTH_URL,
            $credentials ?? new Credentials(self::API_KEY, 'sandbox'),
            $store ?? new MemoryTokenStore(),
            $this->logger,
            $sleep ?? function (float $seconds) {
                $this->sleeps[] = $seconds;
            },
            fn () => $this->random,
            fn () => $this->now,
        );
    }

    private function guzzle(array $config = []): \GuzzleHttp\Client
    {
        return new \GuzzleHttp\Client(['handler' => $this->http->stack] + $config);
    }

    private function mintFailure(?TokenProvider $provider = null, string $audience = 'api.didww.com'): DidwwAuthException
    {
        try {
            ($provider ?? $this->provider())->token($audience);
        } catch (DidwwAuthException $e) {
            return $e;
        }
        $this->fail('Expected a DidwwAuthException');
    }
}
