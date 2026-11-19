<?php

namespace Didww\Internal;

use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Wraps a Guzzle handler so that the curl options of a request carry the
 * stall limit of the SDK (under 1 byte/s for 60 s), below the caller's own
 * CURLOPT_LOW_SPEED_* values. A request that may go to Guzzle's stream
 * handler gets none, as that handler rejects the curl option and its
 * read_timeout ends a stall there: one with "stream" => true, which the
 * default handler sends to it, and one whose handler is the stream handler,
 * directly or as the handler of a HandlerStack.
 *
 * @internal
 */
final class CurlStallLimit
{
    public const OPTIONS = [CURLOPT_LOW_SPEED_LIMIT => 1, CURLOPT_LOW_SPEED_TIME => 60];

    private function __construct()
    {
    }

    public static function wrap(callable $handler): callable
    {
        return static function (#[\SensitiveParameter] RequestInterface $request, #[\SensitiveParameter] array $options) use ($handler): PromiseInterface {
            $curl = $options['curl'] ?? [];
            // A value that is not an array is left for Guzzle to reject.
            if (is_array($curl) && self::reachesCurl($handler, $options)) {
                $options['curl'] = $curl + self::OPTIONS;
            }

            return $handler($request, $options);
        };
    }

    // Whether $handler hands a request with $options to a handler other than
    // Guzzle's stream handler.
    private static function reachesCurl(callable $handler, array $options): bool
    {
        if (!empty($options['stream']) || $handler instanceof StreamHandler) {
            return false;
        }
        if ($handler instanceof HandlerStack) {
            $inner = self::handlerOf($handler);

            return null === $inner || self::reachesCurl($inner, $options);
        }

        return true;
    }

    // The handler of a HandlerStack, which it does not expose; null when it
    // has none or cannot be read.
    private static function handlerOf(HandlerStack $stack): ?callable
    {
        try {
            $handler = (new \ReflectionProperty(HandlerStack::class, 'handler'))->getValue($stack);
        } catch (\ReflectionException) {
            return null;
        }

        return is_callable($handler) ? $handler : null;
    }
}
