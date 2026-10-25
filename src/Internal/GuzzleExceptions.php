<?php

namespace Didww\Internal;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ConnectTimeoutException;
use GuzzleHttp\Exception\HandlerClosedException;
use GuzzleHttp\Exception\NetworkException;
use GuzzleHttp\Exception\NetworkTimeoutException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ResponseException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Utils;

/**
 * @internal
 */
final class GuzzleExceptions
{
    // The Guzzle classes whose constructor takes a message, a request and a
    // previous exception, most specific first.
    private const NETWORK_CLASSES = [ConnectTimeoutException::class, ConnectException::class, NetworkTimeoutException::class, NetworkException::class];

    // The Guzzle classes under TransferException whose constructor takes a
    // message, a request, a code and a previous exception, like it.
    private const REQUEST_CLASSES = [RequestException::class, HandlerClosedException::class];

    private function __construct()
    {
    }

    /**
     * $e with $header removed from its request and from those of the Guzzle
     * exceptions it chains, keeping the messages, codes and responses, and the
     * class: a Guzzle class as is, a subclass of another library as its
     * nearest Guzzle class (a response exception keeps any class, as Guzzle
     * fixes its constructor); $e itself when no request carries the header.
     * Another exception in the chain was thrown during the transfer, so its
     * trace holds the request: it is replaced by a \RuntimeException with its
     * class and message and nothing chained.
     * With $withoutBody the body of a response is dropped as well, for an
     * answer that may hold a secret, such as a token.
     * $e is a sensitive parameter, so that it is not kept in the trace of the new exception.
     */
    public static function withoutHeader(#[\SensitiveParameter] \Throwable $e, string $header, bool $withoutBody = false): \Throwable
    {
        if (!$e instanceof TransferException) {
            return $e;
        }
        $previous = $e->getPrevious();
        if (null !== $previous) {
            $previous = $previous instanceof TransferException
                ? self::withoutHeader($previous, $header, $withoutBody)
                : new \RuntimeException($previous::class.': '.$previous->getMessage());
        }
        $hasBody = $withoutBody && $e instanceof ResponseException && 0 !== $e->getResponse()->getBody()->getSize();
        if (!$e->getRequest()->hasHeader($header) && $previous === $e->getPrevious() && !$hasBody) {
            return $e;
        }
        $request = $e->getRequest()->withoutHeader($header);
        if ($e instanceof ResponseException) {
            $class = $e::class;

            $response = $withoutBody ? $e->getResponse()->withBody(Utils::streamFor('')) : $e->getResponse();

            return new $class($e->getMessage(), $request, $response, $previous);
        }
        foreach (self::NETWORK_CLASSES as $class) {
            if ($e instanceof $class) {
                return new $class($e->getMessage(), $request, $previous);
            }
        }
        foreach (self::REQUEST_CLASSES as $class) {
            if ($e instanceof $class) {
                return new $class($e->getMessage(), $request, $e->getCode(), $previous);
            }
        }

        return new TransferException($e->getMessage(), $request, $e->getCode(), $previous);
    }
}
