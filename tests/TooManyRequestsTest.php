<?php

namespace Didww\Tests;

use Didww\Auth\TokenProvider;
use Didww\Item\Did;
use Didww\Item\EncryptedFile;
use Didww\Item\Export;
use Didww\Item\VoiceInTrunk;
use Didww\Item\VoiceInTrunkGroup;
use Didww\Item\VoiceOutTrunk;
use Didww\RetryAfter;
use Didww\Tests\Support\ConfiguresMockHttp;
use Didww\Tests\Support\MockHttp;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TooManyRequestsTest extends TestCase
{
    use ConfiguresMockHttp;

    private const ID = '41b94706-325e-4704-a433-d65105758836';
    private const EXPORT_URL = 'https://sandbox-api.didww.com/v3/exports/9df99644-f1a5-4a3c-99a4-559d758eb96b.csv.gz';
    private const TOO_MANY_REQUESTS = '{"errors":[{"title":"Too many requests","detail":"The request budget of the account is spent","code":"too_many_requests","status":"429"}]}';
    private const SERVICE_UNAVAILABLE = '{"errors":[{"title":"Service unavailable","detail":"Try again later","code":"service_unavailable","status":"503"}]}';

    protected function tearDown(): void
    {
        TokenProvider::forgetRateLimits();
    }

    /**
     * Each call returns the Retry-After seconds the SDK reports for its answer.
     */
    public static function callProvider(): array
    {
        return [
            'JSON:API list' => [static fn () => RetryAfter::seconds(Did::all())],
            'JSON:API create' => [static fn () => RetryAfter::seconds((new VoiceInTrunk(['name' => 'trunk']))->save())],
            'JSON:API update' => [static function () {
                $trunk = VoiceOutTrunk::build(self::ID);
                $trunk->setName('renamed');

                return RetryAfter::seconds($trunk->save());
            }],
            'JSON:API delete' => [static fn () => RetryAfter::seconds(VoiceInTrunkGroup::build(self::ID)->delete())],
            'export download' => [static function () {
                $retryAfter = 99;
                Export::build(self::ID, ['url' => self::EXPORT_URL])->download(fopen('php://memory', 'w+b'), $retryAfter);

                return $retryAfter;
            }],
            'export download and decompress' => [static function () {
                $retryAfter = 99;
                Export::build(self::ID, ['url' => self::EXPORT_URL])->downloadAndDecompress(fopen('php://memory', 'w+b'), $retryAfter);

                return $retryAfter;
            }],
            'encrypted file upload' => [static fn () => EncryptedFile::upload('fingerprint', 'encrypted-bytes')->getRetryAfter()],
        ];
    }

    #[DataProvider('callProvider')]
    public function testTooManyRequestsReportsRetryAfterAndIsNotRetried(callable $call)
    {
        $this->configureMock()->append(MockHttp::token(), self::answer(429, '30'), self::jsonApi());

        $this->assertSame(30, $call());
        $this->assertSame(2, $this->http->count(), 'the token request and one API request');
    }

    #[DataProvider('callProvider')]
    public function testRetryAfterOfAnotherErrorStatus(callable $call)
    {
        $this->configureMock()->append(MockHttp::token(), self::answer(503, '120', self::SERVICE_UNAVAILABLE));

        $this->assertSame(120, $call());
    }

    public static function notSecondsProvider(): array
    {
        $cases = [];
        foreach (self::callProvider() as $name => [$call]) {
            $cases[$name.', missing'] = [$call, null];
            $cases[$name.', HTTP date'] = [$call, 'Wed, 21 Oct 2026 07:28:00 GMT'];
            $cases[$name.', negative'] = [$call, '-1'];
        }

        return $cases;
    }

    #[DataProvider('notSecondsProvider')]
    public function testRetryAfterThatIsNotSecondsIsNull(callable $call, ?string $retryAfter)
    {
        $this->configureMock()->append(MockHttp::token(), self::answer(429, $retryAfter));

        $this->assertNull($call());
    }

    public function testJsonApiTooManyRequestsIsADocumentError()
    {
        $this->configureMock()->append(MockHttp::token(), self::answer(429, '30'));

        $document = (new VoiceInTrunk(['name' => 'trunk']))->save();

        $this->assertTrue($document->hasErrors());
        $this->assertSame('429', $document->getErrors()->first()->getStatus());
        $this->assertSame('too_many_requests', $document->getErrors()->first()->getCode());
    }

    public function testUploadReportsTheStatus()
    {
        $this->configureMock()->append(MockHttp::token(), self::answer(429, '30'));

        $result = EncryptedFile::upload('fingerprint', 'encrypted-bytes');

        $this->assertFalse($result->success());
        $this->assertSame(429, $result->getResponseCode());
    }

    public function testExportDownloadReportsTheStatus()
    {
        $this->configureMock()->append(MockHttp::token(), self::answer(429, '30'));

        $result = Export::build(self::ID, ['url' => self::EXPORT_URL])->download(fopen('php://memory', 'w+b'));

        $this->assertSame('Export download failed with status 429: The request budget of the account is spent', $result);
    }

    public static function exportDownloadProvider(): array
    {
        return [
            'download' => ['download'],
            'download and decompress' => ['downloadAndDecompress'],
        ];
    }

    #[DataProvider('exportDownloadProvider')]
    public function testSuccessfulExportDownloadClearsRetryAfter(string $method)
    {
        $this->configureMock()->append(MockHttp::token(), new Response(200, ['Retry-After' => '30'], gzencode("a,b\n1,2\n")));
        $retryAfter = 99;

        $this->assertTrue(Export::build(self::ID, ['url' => self::EXPORT_URL])->{$method}(fopen('php://memory', 'w+b'), $retryAfter));

        $this->assertNull($retryAfter);
    }

    #[DataProvider('exportDownloadProvider')]
    public function testExportDownloadTransportFailureClearsRetryAfter(string $method)
    {
        $this->configureMock()->append(MockHttp::token(), new ConnectException('cURL error 7: Failed to connect', new Request('GET', self::EXPORT_URL)));
        $retryAfter = 99;

        $result = Export::build(self::ID, ['url' => self::EXPORT_URL])->{$method}(fopen('php://memory', 'w+b'), $retryAfter);

        $this->assertSame('Export download failed: cURL error 7: Failed to connect', $result);
        $this->assertNull($retryAfter);
    }

    private static function answer(int $status, ?string $retryAfter, string $body = self::TOO_MANY_REQUESTS): Response
    {
        $headers = ['Content-Type' => 'application/vnd.api+json'];
        if (null !== $retryAfter) {
            $headers['Retry-After'] = $retryAfter;
        }

        return new Response($status, $headers, $body);
    }
}
