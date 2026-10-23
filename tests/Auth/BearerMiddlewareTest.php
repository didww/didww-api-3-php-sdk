<?php

namespace Didww\Tests\Auth;

use Didww\Auth\Audience;
use Didww\Auth\BearerMiddleware;
use Didww\Auth\TokenProvider;
use Didww\Configuration;
use Didww\Exception\AuthErrorKind;
use Didww\Exception\DidwwAuthException;
use Didww\Internal\GuzzleExceptions;
use Didww\Item\Did;
use Didww\Item\Export;
use Didww\Item\PublicKey;
use Didww\Tests\Support\ConfiguresMockHttp;
use Didww\Tests\Support\MockHttp;
use Didww\Tests\Support\TraceArgs;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ConnectTimeoutException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

class BearerMiddlewareTest extends TestCase
{
    use ConfiguresMockHttp;

    private const EXPORT_ID = '9df99644-f1a5-4a3c-99a4-559d758eb96b';

    protected function tearDown(): void
    {
        TokenProvider::forgetRateLimits();
    }

    public function testJsonApiRequestCarriesBearerOnly()
    {
        $this->configureMock()->append(MockHttp::token('token-1'), self::jsonApi());

        Did::all();

        $this->assertSame(2, $this->http->count());
        $this->assertSame('/token', $this->http->request(0)->getUri()->getPath());
        $this->assertSame('test-api-key', $this->http->request(0)->getHeaderLine('Api-Key'));
        $request = $this->http->request(1);
        $this->assertSame('https://sandbox-api.didww.com/v3/dids', (string) $request->getUri());
        $this->assertSame('Bearer token-1', $request->getHeaderLine('Authorization'));
        $this->assertFalse($request->hasHeader('Api-Key'));
        $this->assertFalse($request->hasHeader('X-DIDWW-API-Version'));
        $this->assertSame('application/vnd.api+json', $request->getHeaderLine('Accept'));
    }

    public function testTokenReusedAcrossRequests()
    {
        $this->configureMock()->append(MockHttp::token('token-1'), self::jsonApi(), self::jsonApi());

        Did::all();
        Did::all();

        $this->assertCount(1, $this->http->requestsTo('/token'));
        $this->assertSame('Bearer token-1', $this->http->request(2)->getHeaderLine('Authorization'));
    }

    public function testRetriesOnceWithNewTokenAfter401()
    {
        $this->configureMock()->append(MockHttp::token('token-1'), self::unauthorized(), MockHttp::token('token-2'), self::jsonApi());

        $document = Did::all();

        $this->assertFalse($document->hasErrors());
        $this->assertCount(2, $this->http->requestsTo('/token'));
        $this->assertSame('Bearer token-1', $this->http->request(1)->getHeaderLine('Authorization'));
        $this->assertSame('Bearer token-2', $this->http->request(3)->getHeaderLine('Authorization'));
    }

    public function testReplaysPatchBodyAfter401()
    {
        $response = '{"data":{"id":"'.self::EXPORT_ID.'","type":"exports","attributes":{"status":"completed","external_reference_id":"ref-1"}}}';
        $this->configureMock()->append(MockHttp::token('token-1'), self::unauthorized(), MockHttp::token('token-2'), self::jsonApi($response));
        $export = Export::build(self::EXPORT_ID);
        $export->setExternalReferenceId('ref-1');

        $document = $export->save();

        $this->assertFalse($document->hasErrors());
        $first = $this->http->request(1);
        $second = $this->http->request(3);
        $this->assertSame('PATCH', $second->getMethod());
        $this->assertNotSame('', (string) $first->getBody());
        $this->assertSame((string) $first->getBody(), (string) $second->getBody());
    }

    public function testSecond401DropsReplacementToken()
    {
        $this->configureMock()->append(
            MockHttp::token('token-1'),
            self::unauthorized(),
            MockHttp::token('token-2'),
            self::unauthorized(),
            MockHttp::token('token-3'),
            self::jsonApi(),
        );

        $document = Did::all();
        $this->assertTrue($document->hasErrors());
        $this->assertSame('401', $document->getErrors()->first()->getStatus());
        $this->assertSame(4, $this->http->count());

        Did::all();
        $this->assertCount(3, $this->http->requestsTo('/token'));
        $this->assertSame('Bearer token-3', $this->http->request(5)->getHeaderLine('Authorization'));
    }

    public function testForbiddenNotRetried()
    {
        $this->configureMock()->append(MockHttp::token('token-1'), self::jsonApi('{"errors":[{"title":"Forbidden","code":"insufficient_scope","status":"403"}]}', 403), self::jsonApi());

        $document = Did::all();

        $this->assertTrue($document->hasErrors());
        $this->assertSame(2, $this->http->count());

        Did::all();
        $this->assertCount(1, $this->http->requestsTo('/token'));
    }

    public function testNonSeekableBodyNotReplayed()
    {
        $this->configureMock()->append(MockHttp::token('token-1'), self::unauthorized());

        $response = Configuration::getHttpClient()->request('POST', 'https://sandbox-api.didww.com/v3/encrypted_files', [
            'body' => new NoSeekStream(Utils::streamFor('payload')),
            'http_errors' => false,
        ]);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(2, $this->http->count());
    }

    public function testResourceSinkCutBackBeforeRetry()
    {
        $this->configureMock()->append(MockHttp::token('token-1'), self::unauthorized(), MockHttp::token('token-2'), new Response(200, [], 'REALDATA'));
        $sink = fopen('php://memory', 'w+b');
        fwrite($sink, 'head:');

        $response = Configuration::getHttpClient()->request('GET', 'https://sandbox-api.didww.com/v3/exports/1.csv.gz', ['sink' => $sink]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(4, $this->http->count());
        rewind($sink);
        $this->assertSame('head:REALDATA', stream_get_contents($sink));
    }

    public function testAppendModeSinkKeepsItsContent()
    {
        $this->configureMock()->append(MockHttp::token('token-1'), self::unauthorized(), MockHttp::token('token-2'), new Response(200, [], 'REALDATA'));
        $path = tempnam(sys_get_temp_dir(), 'didww_sink_');
        file_put_contents($path, "EXISTING-LINE\n");
        $sink = fopen($path, 'a');

        try {
            $response = Configuration::getHttpClient()->request('GET', 'https://sandbox-api.didww.com/v3/exports/1.csv.gz', ['sink' => $sink]);
            fclose($sink);

            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame(4, $this->http->count());
            $this->assertSame("EXISTING-LINE\nREALDATA", file_get_contents($path));
        } finally {
            unlink($path);
        }
    }

    public function testResourceSinkInsideItsContentNotReplayed()
    {
        $this->configureMock()->append(MockHttp::token('token-1'), self::unauthorized());
        $sink = fopen('php://memory', 'w+b');
        fwrite($sink, str_repeat('x', 100));
        rewind($sink);

        $response = Configuration::getHttpClient()->request('GET', 'https://sandbox-api.didww.com/v3/exports/1.csv.gz', [
            'sink' => $sink,
            'http_errors' => false,
        ]);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(2, $this->http->count());
    }

    public function testStreamSinkInsideItsContentNotReplayed()
    {
        $this->configureMock()->append(MockHttp::token('token-1'), self::unauthorized(), MockHttp::token('token-2'), new Response(200, [], 'OK'));
        $sink = Utils::streamFor(str_repeat('x', 100));
        $sink->rewind();

        $response = Configuration::getHttpClient()->request('GET', 'https://sandbox-api.didww.com/v3/exports/1.csv.gz', [
            'sink' => $sink,
            'http_errors' => false,
        ]);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(2, $this->http->count());
        $this->assertSame(100, $sink->getSize());
    }

    public function testWrittenStreamSinkNotReplayed()
    {
        $this->configureMock()->append(MockHttp::token('token-1'), self::unauthorized(), MockHttp::token('token-2'), new Response(200, [], 'REALDATA'));
        $sink = new NoSeekStream(Utils::streamFor(fopen('php://memory', 'w+b')));

        $response = Configuration::getHttpClient()->request('GET', 'https://sandbox-api.didww.com/v3/exports/1.csv.gz', [
            'sink' => $sink,
            'http_errors' => false,
        ]);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(2, $this->http->count());
    }

    public function testPublicKeysWithoutToken()
    {
        $this->configureMock()->append(self::jsonApi(), new Response(200), MockHttp::token('token-1'), new Response(200));

        PublicKey::all();
        Configuration::getHttpClient()->request('GET', 'https://sandbox-api.didww.com/v3/public_keys/1');
        Configuration::getHttpClient()->request('GET', 'https://sandbox-api.didww.com/v3/public_keys_x');

        foreach ([0, 1] as $i) {
            $this->assertFalse($this->http->request($i)->hasHeader('Authorization'));
            $this->assertFalse($this->http->request($i)->hasHeader('Api-Key'));
        }
        $this->assertSame('/token', $this->http->request(2)->getUri()->getPath());
        $this->assertSame('Bearer token-1', $this->http->request(3)->getHeaderLine('Authorization'));
    }

    public function testAudiencePerRequest()
    {
        $this->configureMock()->append(MockHttp::token('vin-token', [Audience::VOICE_IN]), new Response(200), new Response(200));

        Configuration::getHttpClient()->request('GET', 'https://api-vin-sandbox.didww.com/v3/prices.csv.gz', [BearerMiddleware::AUDIENCE_OPTION => Audience::VOICE_IN]);
        Configuration::getHttpClient()->request('GET', 'https://sandbox-api.didww.com/storage/public/file.pdf', [BearerMiddleware::AUDIENCE_OPTION => false]);

        $this->assertJsonStringEqualsJsonString('{"aud":["api-vin.didww.com"],"api_version":"2026-10-22"}', (string) $this->http->request(0)->getBody());
        $this->assertSame('Bearer vin-token', $this->http->request(1)->getHeaderLine('Authorization'));
        $this->assertArrayNotHasKey(BearerMiddleware::AUDIENCE_OPTION, $this->http->options(1));
        $this->assertFalse($this->http->request(2)->hasHeader('Authorization'));
    }

    public function testStripsIfNoneMatch()
    {
        $this->configureMock(['headers' => ['If-None-Match' => '"default"']])->append(MockHttp::token(), new Response(200));

        Configuration::getHttpClient()->request('GET', 'https://sandbox-api.didww.com/v3/dids', ['headers' => ['If-None-Match' => '"etag"']]);

        $this->assertFalse($this->http->request(1)->hasHeader('If-None-Match'));
    }

    public function testTransportErrorCarriesNoToken()
    {
        $this->configureMock()->append(MockHttp::token('secret-token'), fn ($request) => new ConnectTimeoutException('cURL error 28: Connection timed out', $request));

        $e = TraceArgs::keepingArgs(function () {
            try {
                Configuration::getHttpClient()->request('GET', 'https://sandbox-api.didww.com/v3/dids');
            } catch (ConnectException $e) {
                return $e;
            }
            $this->fail('No exception');
        });

        $this->assertSame('Bearer secret-token', $this->http->request(1)->getHeaderLine('Authorization'));
        $this->assertFalse($e->getRequest()->hasHeader('Authorization'));
        $this->assertSame('cURL error 28: Connection timed out', $e->getMessage());
        $this->assertSame(ConnectTimeoutException::class, $e::class);
        // The unredacted exception is not kept in the trace either. (The other
        // frames keep the request history of the mock stack in their options.)
        $traceArgs = TraceArgs::ofSdkFrames($e, GuzzleExceptions::class);
        $this->assertStringContainsString('GuzzleExceptions::withoutHeader', $traceArgs);
        $this->assertStringNotContainsString('secret-token', $traceArgs);
        $this->assertNotEmpty((new \ReflectionParameter([BearerMiddleware::class, 'send'], 'token'))->getAttributes(\SensitiveParameter::class));
    }

    public function testOtherErrorKeepsNoTokenInSdkFrames()
    {
        $this->configureMock()->append(MockHttp::token('secret-token'), static function () {
            throw new \TypeError('broken handler');
        });

        $e = TraceArgs::keepingArgs(function () {
            try {
                Configuration::getHttpClient()->request('GET', 'https://sandbox-api.didww.com/v3/dids');
            } catch (\TypeError $e) {
                return $e;
            }
            $this->fail('No exception');
        });

        $traceArgs = TraceArgs::ofSdkFrames($e, 'Didww\\Internal\\CurlStallLimit');
        $this->assertStringContainsString('SensitiveParameterValue', $traceArgs);
        $this->assertFalse(str_contains($traceArgs, 'secret-token'));
    }

    public function testRedirectsNotFollowed()
    {
        $this->configureMock()->append(MockHttp::token(), new Response(302, ['Location' => 'https://elsewhere.example.com/']), new Response(200));

        $response = Configuration::getHttpClient()->request('GET', 'https://sandbox-api.didww.com/v3/dids', ['allow_redirects' => true]);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(2, $this->http->count());
    }

    public function testHttpErrorsOptionAppliedAfterRetry()
    {
        $this->configureMock()->append(MockHttp::token('token-1'), self::unauthorized(), MockHttp::token('token-2'), new Response(404));

        try {
            Configuration::getHttpClient()->request('GET', 'https://sandbox-api.didww.com/v3/dids/1');
            $this->fail('Expected a RequestException');
        } catch (RequestException $e) {
            $this->assertSame(404, $e->getResponse()->getStatusCode());
        }
        $this->assertSame(4, $this->http->count());
    }

    public function testAuthErrorsSurfaceUnchanged()
    {
        $this->configureMock()->append(MockHttp::tokenError(401, 'invalid_client.json'));

        try {
            Did::all();
            $this->fail('Expected a DidwwAuthException');
        } catch (DidwwAuthException $e) {
            $this->assertSame(AuthErrorKind::INVALID_CLIENT, $e->getKind());
        }
        $this->assertSame(1, $this->http->count());
    }

    public function testTransportErrorsSurfaceUnchanged()
    {
        $this->configureMock()->append(MockHttp::token(), new ConnectException('cURL error 7: Failed to connect', new Request('GET', 'https://sandbox-api.didww.com/v3/dids')));

        $this->expectException(ConnectException::class);

        Did::all();
    }
}
