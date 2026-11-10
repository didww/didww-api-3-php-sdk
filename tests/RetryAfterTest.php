<?php

namespace Didww\Tests;

use Didww\RetryAfter;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Swis\JsonApi\Client\Document;

class RetryAfterTest extends TestCase
{
    public static function headerProvider(): array
    {
        return [
            'seconds' => ['30', 30],
            'zero' => ['0', 0],
            'nine digits' => ['999999999', 999999999],
            'ten digits' => ['1234567890', null],
            'plus sign' => ['+5', null],
            'minus sign' => ['-1', null],
            'exponent' => ['1e3', null],
            'fraction' => ['1.5', null],
            'HTTP date' => ['Wed, 21 Oct 2026 07:28:00 GMT', null],
            'empty' => ['', null],
        ];
    }

    #[DataProvider('headerProvider')]
    public function testSeconds(string $header, ?int $seconds)
    {
        $response = new Response(429, ['Retry-After' => $header]);

        $this->assertSame($seconds, RetryAfter::seconds($response));
        $this->assertSame($seconds, RetryAfter::seconds((new Document())->setResponse($response)));
    }

    public function testSurroundingSpacesAreIgnored()
    {
        // Guzzle trims header values itself; another PSR-7 implementation may not.
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getHeaderLine')->willReturn(" 30\t");

        $this->assertSame(30, RetryAfter::seconds($response));
    }

    public function testNoAnswer()
    {
        $this->assertNull(RetryAfter::seconds(null));
        $this->assertNull(RetryAfter::seconds(new Document()));
        $this->assertNull(RetryAfter::seconds(new Response(429)));
    }
}
