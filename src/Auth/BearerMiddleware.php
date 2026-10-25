<?php

namespace Didww\Auth;

use Didww\Internal\GuzzleExceptions;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Guzzle middleware that authenticates every request with a bearer token for
 * its audience. The API key is never sent here: it goes to the token service
 * only.
 *
 * The audience is the AUDIENCE_OPTION request option: a string audience, or
 * false for no token (Guzzle drops request options set to null). Without the option, public_keys requests under the API
 * base path go without a token and every other request uses Audience::API.
 *
 * A 401 answer evicts the refused token and the request is sent once more with
 * a newly minted one, when its body can be sent again (empty or seekable) and
 * its sink can take the new answer: none, a path, a seekable resource written
 * at its end (cut back to that size), a seekable stream written at its end
 * that the refused answer was not written to, or another stream the refused
 * answer was not written to.
 * Otherwise the 401 is returned. A 403 is never retried. Redirects are never followed, so that a token is not
 * sent to another URL. Token errors (DidwwAuthException) propagate unchanged;
 * transport errors propagate without the token in their request. The middleware works synchronously: a promise it
 * returns is already settled. Only AUDIENCE_OPTION is public API.
 */
final class BearerMiddleware
{
    public const AUDIENCE_OPTION = 'didww_audience';

    /**
     * @internal
     */
    public function __construct(
        private readonly TokenProvider $tokens,
        private readonly string $apiBasePath,
    ) {
    }

    /**
     * @internal
     */
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
            $audience = $this->audience($request, $options);
            $httpErrors = !empty($options['http_errors']);
            unset($options[self::AUDIENCE_OPTION]);
            $options['allow_redirects'] = false;
            $options['http_errors'] = false;

            $request = $request->withoutHeader('If-None-Match');
            if (false === $audience || null === $audience || '' === $audience) {
                $response = $handler($request->withoutHeader('Authorization'), $options)->wait();

                return $this->settle($request, $response, $httpErrors);
            }

            $sink = $options['sink'] ?? null;
            $sinkMark = self::sinkMark($sink);
            $token = $this->tokens->token($audience);
            $response = $this->send($handler, $request, $options, $token);
            if (401 !== $response->getStatusCode()) {
                return $this->settle($request, $response, $httpErrors);
            }

            $this->tokens->invalidate($audience, $token->getAccessToken());
            if (!$this->rewind($request) || !self::resetSink($sink, $sinkMark)) {
                return $this->settle($request, $response, $httpErrors);
            }
            if (is_resource($sink)) {
                // The refused answer's body may wrap the caller's resource:
                // Guzzle's own handlers leave it open when the body is
                // released, another handler's body could close it.
                $response->getBody()->detach();
            }
            $token = $this->tokens->token($audience);
            $response = $this->send($handler, $request, $options, $token);
            if (401 === $response->getStatusCode()) {
                $this->tokens->invalidate($audience, $token->getAccessToken());
            }

            return $this->settle($request, $response, $httpErrors);
        };
    }

    // The AUDIENCE_OPTION of the request; without it, none for a public
    // endpoint and Audience::API for any other.
    private function audience(RequestInterface $request, array $options): mixed
    {
        if (array_key_exists(self::AUDIENCE_OPTION, $options)) {
            return $options[self::AUDIENCE_OPTION];
        }

        return $this->isPublicEndpoint($request) ? null : Audience::API;
    }

    // A transport error is rethrown without the token in its request.
    private function send(callable $handler, RequestInterface $request, array $options, #[\SensitiveParameter] AccessToken $token): ResponseInterface
    {
        try {
            return $handler($request->withHeader('Authorization', 'Bearer '.$token->getAccessToken()), $options)->wait();
        } catch (TransferException $e) {
            throw GuzzleExceptions::withoutHeader($e, 'Authorization');
        }
    }

    // Applies the caller's http_errors option, which the inner handlers must not
    // apply before a 401 is seen here.
    private function settle(RequestInterface $request, ResponseInterface $response, bool $httpErrors): PromiseInterface
    {
        if ($httpErrors && $response->getStatusCode() >= 400) {
            throw RequestException::create($request, $response);
        }

        return Create::promiseFor($response);
    }

    // Rewinds the body for the second send; false when it cannot be sent again.
    private function rewind(RequestInterface $request): bool
    {
        $body = $request->getBody();
        if (!$body->isSeekable()) {
            return 0 === $body->getSize();
        }
        try {
            $body->rewind();
        } catch (\RuntimeException) {
            return false;
        }

        return true;
    }

    // Where a resource or stream sink stands before the first send, and its
    // size; null for a path or no sink, which the handler opens afresh for each
    // send. A resource in append mode writes at its end whatever ftell() says.
    private static function sinkMark(mixed $sink): ?array
    {
        try {
            if (is_resource($sink)) {
                $stat = fstat($sink);
                $size = false === $stat ? null : $stat['size'];
                $position = str_contains(stream_get_meta_data($sink)['mode'], 'a') ? $size : ftell($sink);

                return [$position ?? false, $size];
            }
            if ($sink instanceof StreamInterface) {
                return [$sink->tell(), $sink->getSize()];
            }
        } catch (\RuntimeException) {
            return [false, null];
        }

        return null;
    }

    // Undoes what the refused answer wrote to the sink before the second send;
    // false when it cannot. A seekable resource written at its end is cut back
    // to its size, so that no earlier content is lost. Any other sink must not
    // have taken the refused answer: a seekable stream cannot be cut, so it
    // must be written at its end and must not have grown, and any other must
    // not have moved (the SDK's own price and export sinks keep error bodies
    // apart).
    private static function resetSink(mixed $sink, ?array $mark): bool
    {
        if (null === $mark) {
            return true;
        }
        [$position, $size] = $mark;
        if (false === $position) {
            return false;
        }
        try {
            if (is_resource($sink)) {
                if (stream_get_meta_data($sink)['seekable']) {
                    return $position === $size && @ftruncate($sink, $size) && 0 === @fseek($sink, $size);
                }

                return ftell($sink) === $position;
            }
            if ($sink->isSeekable()) {
                if (null === $size || $position !== $size || $sink->getSize() !== $size) {
                    return false;
                }
                $sink->seek($position);

                return true;
            }

            return $sink->tell() === $position;
        } catch (\RuntimeException) {
            return false;
        }
    }

    // Whether the path names a public resource (the first segment under the API
    // base path), which is called without a token.
    private function isPublicEndpoint(RequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();
        $base = rtrim($this->apiBasePath, '/');
        if ('' !== $base) {
            if (!str_starts_with($path, $base.'/')) {
                return false;
            }
            $path = substr($path, strlen($base));
        }

        return 'public_keys' === explode('/', ltrim($path, '/'))[0];
    }
}
