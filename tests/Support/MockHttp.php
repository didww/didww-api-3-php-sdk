<?php

namespace Didww\Tests\Support;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * A Guzzle handler stack answering from a queue and recording every request
 * it sends, token requests included.
 */
final class MockHttp
{
    public readonly MockHandler $handler;
    public readonly HandlerStack $stack;
    public array $history = [];

    public function __construct(array $queue = [])
    {
        $this->handler = new MockHandler($queue);
        $this->stack = HandlerStack::create($this->handler);
        $this->stack->push(Middleware::history($this->history));
    }

    public function append(...$responses): void
    {
        $this->handler->append(...$responses);
    }

    public function count(): int
    {
        return count($this->history);
    }

    public function request(int $index): RequestInterface
    {
        return $this->history[$index]['request'];
    }

    public function options(int $index): array
    {
        return $this->history[$index]['options'];
    }

    /**
     * @return list<RequestInterface>
     */
    public function requestsTo(string $path): array
    {
        $requests = [];
        foreach ($this->history as $entry) {
            if ($entry['request']->getUri()->getPath() === $path) {
                $requests[] = $entry['request'];
            }
        }

        return $requests;
    }

    public static function token(string $accessToken = 'token-1', array $audience = ['api.didww.com'], string $scope = 'full', string $apiVersion = '2026-10-22', int $expiresIn = 900): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
            'aud' => $audience,
            'scope' => $scope,
            'api_version' => $apiVersion,
        ]));
    }

    public static function tokenError(int $status, string $fixture, array $headers = []): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'] + $headers, file_get_contents(__DIR__.'/../fixtures/token/'.$fixture));
    }
}
