<?php

namespace Didww\Tests\Prices\Support;

use Didww\Auth\Audience;
use Didww\Tests\Support\ConfiguresMockHttp;
use Didww\Tests\Support\MockHttp;
use GuzzleHttp\Psr7\Response;

/**
 * Fixtures and canned answers of the prices services.
 */
trait PricesHelpers
{
    use ConfiguresMockHttp;

    private const LAST_MODIFIED = 'Wed, 14 Oct 2026 06:00:00 GMT';
    private const OTHER_LAST_MODIFIED = 'Thu, 15 Oct 2026 06:00:00 GMT';

    private static function fixture(string $name): string
    {
        return file_get_contents(__DIR__.'/../../fixtures/prices/'.$name);
    }

    private static function pricesJson(int $status, string $body, array $headers = []): Response
    {
        return new Response($status, ['Content-Type' => 'application/vnd.api+json'] + $headers, $body);
    }

    private static function priceFile(string $body, string $lastModified = self::LAST_MODIFIED): Response
    {
        return new Response(200, [
            'Content-Type' => 'application/gzip',
            'Content-Disposition' => 'attachment; filename="voice_in_prices.csv.gz"',
            'Content-Length' => (string) strlen($body),
            'Last-Modified' => $lastModified,
            'Cache-Control' => 'private',
            'Accept-Ranges' => 'none',
        ], $body);
    }

    private static function notModified(string $lastModified = self::LAST_MODIFIED): Response
    {
        return new Response(304, [
            'Content-Disposition' => 'attachment; filename="voice_in_prices.csv.gz"',
            'Last-Modified' => $lastModified,
        ]);
    }

    private static function servingFailure(?string $retryAfter): Response
    {
        return self::pricesJson(503, self::fixture('errors/serving_failure.json'), null === $retryAfter ? [] : ['Retry-After' => $retryAfter]);
    }

    private static function unauthorizedPrices(): Response
    {
        return self::pricesJson(401, '{"errors":[{"title":"Unauthorized","detail":"Authorization failed","code":"401","status":"401"}]}');
    }

    private static function tokenFor(string $audience, string $accessToken = 'token-1'): Response
    {
        return MockHttp::token($accessToken, [$audience]);
    }

    private static function voiceInToken(string $accessToken = 'token-1'): Response
    {
        return self::tokenFor(Audience::VOICE_IN, $accessToken);
    }

    /**
     * The requests other than token requests.
     *
     * @return list<\Psr\Http\Message\RequestInterface>
     */
    private function serviceRequests(): array
    {
        $requests = [];
        foreach ($this->http->history as $entry) {
            if ('/token' !== $entry['request']->getUri()->getPath()) {
                $requests[] = $entry['request'];
            }
        }

        return $requests;
    }

    /**
     * The options of the requests other than token requests.
     *
     * @return list<array>
     */
    private function serviceOptions(): array
    {
        $options = [];
        foreach ($this->http->history as $entry) {
            if ('/token' !== $entry['request']->getUri()->getPath()) {
                $options[] = $entry['options'];
            }
        }

        return $options;
    }

    /**
     * The audiences of every token request, in order.
     *
     * @return list<string>
     */
    private function mintAudiences(): array
    {
        $audiences = [];
        foreach ($this->http->requestsTo('/token') as $request) {
            array_push($audiences, ...json_decode((string) $request->getBody(), true)['aud']);
        }

        return $audiences;
    }

    /**
     * @return array<string, string>
     */
    private static function query(\Psr\Http\Message\RequestInterface $request): array
    {
        $query = [];
        foreach (explode('&', $request->getUri()->getQuery()) as $pair) {
            [$name, $value] = explode('=', $pair, 2) + [1 => ''];
            $query[rawurldecode($name)] = rawurldecode($value);
        }

        return $query;
    }
}
