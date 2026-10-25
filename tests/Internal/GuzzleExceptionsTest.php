<?php

namespace Didww\Tests\Internal;

use Didww\Internal\GuzzleExceptions;
use Didww\Tests\Support\TraceArgs;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ConnectTimeoutException;
use GuzzleHttp\Exception\NetworkException;
use GuzzleHttp\Exception\NetworkTimeoutException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ResponseException;
use GuzzleHttp\Exception\ResponseTimeoutException;
use GuzzleHttp\Exception\ResponseTransferException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GuzzleExceptionsTest extends TestCase
{
    public static function exceptionProvider(): array
    {
        $request = new Request('POST', 'https://auth.example.com/token', ['Api-Key' => 'secret-key', 'Accept' => 'application/json']);
        $response = new Response(503);

        return [
            'connect' => [new ConnectException('cURL error 7: Failed to connect', $request)],
            'connect timeout' => [new ConnectTimeoutException('cURL error 28: Connection timed out', $request)],
            'network timeout' => [new NetworkTimeoutException('cURL error 28: Operation timed out', $request)],
            'network' => [new NetworkException('cURL error 56: Recv failure', $request)],
            'request' => [new RequestException('cURL error 60: SSL certificate problem', $request, 7)],
            'response' => [new ResponseException('Unable to write to stream', $request, $response)],
            'response transfer' => [new ResponseTransferException('cURL error 18: transfer closed', $request, $response)],
            'response timeout' => [new ResponseTimeoutException('cURL error 28: Operation timed out', $request, $response)],
            'server error' => [new ServerException('Server error', $request, $response)],
            'transfer' => [new TransferException('Transfer failed', $request, 3)],
        ];
    }

    #[DataProvider('exceptionProvider')]
    public function testHeaderRemovedKeepingClassCodeAndResponse(TransferException $e)
    {
        $clean = GuzzleExceptions::withoutHeader($e, 'Api-Key');

        $this->assertSame($e::class, $clean::class);
        $this->assertSame($e->getMessage(), $clean->getMessage());
        $this->assertSame($e->getCode(), $clean->getCode());
        $this->assertFalse($clean->getRequest()->hasHeader('Api-Key'));
        $this->assertSame('application/json', $clean->getRequest()->getHeaderLine('Accept'));
        if ($e instanceof ResponseException) {
            $this->assertSame($e->getResponse(), $clean->getResponse());
        }
    }

    public function testSubclassOfAnotherLibraryBecomesItsGuzzleClass()
    {
        $e = new class('Connection refused', new Request('GET', 'https://api.example.com/v3/dids', ['Authorization' => 'Bearer secret-token'])) extends ConnectException {
        };

        $clean = GuzzleExceptions::withoutHeader($e, 'Authorization');

        $this->assertSame(ConnectException::class, $clean::class);
        $this->assertFalse($clean->getRequest()->hasHeader('Authorization'));
    }

    public function testChainedGuzzleExceptionRedactedToo()
    {
        $request = new Request('GET', 'https://api.example.com/v3/dids', ['Authorization' => 'Bearer secret-token']);
        $e = new RequestException('Error completing request', $request->withoutHeader('Authorization'), 0, new ConnectTimeoutException('cURL error 28', $request));

        $clean = GuzzleExceptions::withoutHeader($e, 'Authorization');

        $this->assertNotSame($e, $clean);
        $this->assertSame(ConnectTimeoutException::class, $clean->getPrevious()::class);
        $this->assertFalse($clean->getPrevious()->getRequest()->hasHeader('Authorization'));
    }

    public function testUnchangedWithoutTheHeader()
    {
        $e = new ConnectException('Connection refused', new Request('GET', 'https://api.example.com/v3/dids'));

        $this->assertSame($e, GuzzleExceptions::withoutHeader($e, 'Authorization'));
    }

    public function testOtherChainedExceptionReplacedWithoutItsTrace()
    {
        $inner = TraceArgs::keepingArgs(static function () {
            try {
                (static function (array $options) { // NOSONAR $options puts the key into the trace under test
                    throw new \InvalidArgumentException('Status code must be an integer value between 1xx and 5xx.');
                })(['headers' => ['Api-Key' => 'secret-key']]);
            } catch (\InvalidArgumentException $e) {
                return $e;
            }
        });
        $e = new RequestException('An error was encountered while creating the response', new Request('POST', 'https://auth.example.com/token', ['Api-Key' => 'secret-key']), 0, $inner);

        $clean = GuzzleExceptions::withoutHeader($e, 'Api-Key');

        $this->assertStringContainsString('secret-key', print_r($inner->getTrace(), true));
        $previous = $clean->getPrevious();
        $this->assertSame(\RuntimeException::class, $previous::class);
        $this->assertSame('InvalidArgumentException: Status code must be an integer value between 1xx and 5xx.', $previous->getMessage());
        $this->assertNull($previous->getPrevious());
        $this->assertStringNotContainsString('secret-key', print_r($previous->getTrace(), true));
    }
}
