<?php

namespace Didww\Tests;

use Didww\Auth\TokenProvider;
use Didww\Exception\DidwwAuthException;
use Didww\Item\EncryptedFile;
use Didww\Tests\Support\ConfiguresMockHttp;
use Didww\Tests\Support\MockHttp;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class EncryptedFileUploadTest extends TestCase
{
    use ConfiguresMockHttp;
    private const FINGERPRINT = 'c74684d7863639169c21c4d04747f8d6fa05cfe3:::8a586bd37fa0000501715321b2e6a7b3ca57894c';
    private const CREATED = '{"data":{"id":"f6a7b890-1234-5678-9abc-def123456789","type":"encrypted_files","attributes":{"description":"passport.pdf","expires_at":"2026-10-29T10:00:00.000Z"}}}';

    protected function tearDown(): void
    {
        TokenProvider::forgetRateLimits();
    }

    public function testUploadSendsMultipartWithBearer()
    {
        $this->configureMock()->append(MockHttp::token('token-1'), new Response(201, ['Content-Type' => 'application/vnd.api+json'], self::CREATED));

        $result = EncryptedFile::upload(self::FINGERPRINT, 'encrypted-bytes', 'passport.pdf', 'b0undary');

        $this->assertTrue($result->success());
        $this->assertSame(201, $result->getResponseCode());
        $this->assertSame('f6a7b890-1234-5678-9abc-def123456789', $result->getId());

        $request = $this->http->request(1);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://sandbox-api.didww.com/v3/encrypted_files', (string) $request->getUri());
        $this->assertSame('Bearer token-1', $request->getHeaderLine('Authorization'));
        $this->assertFalse($request->hasHeader('Api-Key'));
        $this->assertFalse($request->hasHeader('X-DIDWW-API-Version'));
        $this->assertSame('multipart/form-data; boundary=-------------b0undary', $request->getHeaderLine('Content-Type'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertMatchesRegularExpression('#\Adidww-php-sdk/#', $request->getHeaderLine('User-Agent'));
        $body = (string) $request->getBody();
        $this->assertStringContainsString("name=\"encrypted_files[encryption_fingerprint]\"\r\n\r\n".self::FINGERPRINT."\r\n", $body);
        $this->assertStringContainsString("name=\"encrypted_files[description]\"\r\n\r\npassport.pdf\r\n", $body);
        $this->assertStringContainsString('name="encrypted_files[file]"; filename="file.enc"', $body);
        $this->assertStringContainsString("\r\n\r\nencrypted-bytes\r\n---------------b0undary--\r\n", $body);
        $this->assertSame((string) strlen($body), $request->getHeaderLine('Content-Length'));
    }

    public function testUploadReplayedAfter401()
    {
        $this->configureMock()->append(MockHttp::token('token-1'), self::unauthorized(), MockHttp::token('token-2'), new Response(201, [], self::CREATED));

        $result = EncryptedFile::upload(self::FINGERPRINT, 'encrypted-bytes', null, 'b0undary');

        $this->assertTrue($result->success());
        $this->assertSame('Bearer token-2', $this->http->request(3)->getHeaderLine('Authorization'));
        $this->assertSame((string) $this->http->request(1)->getBody(), (string) $this->http->request(3)->getBody());
    }

    public function testUploadValidationError()
    {
        $this->configureMock()->append(MockHttp::token(), new Response(422, [], '{"errors":[{"title":"Invalid fingerprint"}]}'));

        $result = EncryptedFile::upload('bad', 'encrypted-bytes');

        $this->assertFalse($result->success());
        $this->assertTrue($result->hasErrors());
        $this->assertSame([['title' => 'Invalid fingerprint']], $result->getErrors());
    }

    public function testUploadErrorWithoutErrorsKey()
    {
        $this->configureMock()->append(MockHttp::token(), new Response(413, [], '{"message":"too large"}'));

        $result = EncryptedFile::upload(self::FINGERPRINT, 'encrypted-bytes');

        $this->assertTrue($result->hasErrors());
        $this->assertSame([], $result->getErrors());
    }

    public function testUploadTransportFailure()
    {
        $this->configureMock()->append(MockHttp::token(), new ConnectException('cURL error 7: Failed to connect', new Request('POST', 'https://sandbox-api.didww.com/v3/encrypted_files')));

        $result = EncryptedFile::upload(self::FINGERPRINT, 'encrypted-bytes');

        $this->assertFalse($result->success());
        $this->assertSame(0, $result->getResponseCode());
    }

    public function testUploadAuthFailureThrows()
    {
        $this->configureMock()->append(MockHttp::tokenError(401, 'invalid_client.json'));

        $this->expectException(DidwwAuthException::class);

        EncryptedFile::upload(self::FINGERPRINT, 'encrypted-bytes');
    }
}
