<?php

namespace Didww;

use Psr\Http\Message\ResponseInterface;
use Swis\JsonApi\Client\Interfaces\DocumentInterface;

/**
 * Reads the Retry-After of an answer of the API.
 *
 * A 429 (`too_many_requests`) means that the request budget of the account
 * for the current window is spent. The SDK does not retry it, as a write may
 * not be safe to send twice: wait the Retry-After seconds before sending the
 * request again.
 */
final class RetryAfter
{
    private function __construct()
    {
        // Static helpers only; not meant to be instantiated.
    }

    /**
     * The Retry-After of the answer in seconds, whatever its status; null when
     * it is missing or not a number of seconds (an HTTP date is ignored), or
     * when there is no answer.
     *
     * @param DocumentInterface|ResponseInterface|null $answer the document of a JSON:API call, or a response
     */
    public static function seconds(DocumentInterface|ResponseInterface|null $answer): ?int
    {
        $response = $answer instanceof DocumentInterface ? $answer->getResponse() : $answer;
        if (null === $response) {
            return null;
        }
        $value = trim($response->getHeaderLine('Retry-After'));

        return preg_match('/\A\d{1,9}\z/', $value) ? (int) $value : null;
    }
}
