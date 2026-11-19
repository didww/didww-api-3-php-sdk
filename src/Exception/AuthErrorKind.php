<?php

namespace Didww\Exception;

/**
 * The reason why an access token could not be obtained.
 */
enum AuthErrorKind: string
{
    /** The token service refused the API key (401). It is not retried. */
    case INVALID_CLIENT = 'invalid_client';

    /** The token service rejected the request (400), for example an unknown scope. */
    case INVALID_REQUEST = 'invalid_request';

    /**
     * The token service answered 429 (too many token requests). Further token
     * requests fail without a request until the Retry-After time plus a small
     * random delay has passed; such a refusal reports the time still left, so
     * retry in a loop rather than once.
     */
    case RATE_LIMITED = 'rate_limited';

    /** The last attempt to reach the token service was answered with a 5xx. */
    case SERVER_ERROR = 'server_error';

    /** The last attempt could not reach the token service or timed out, TLS failed, or the token request could not be sent. */
    case CONNECTION_ERROR = 'connection_error';

    /** The granted token is empty or does not cover the requested audience. */
    case INVALID_GRANT = 'invalid_grant';

    /** The token service answered with a status the SDK does not expect, such as a redirect. */
    case UNEXPECTED_STATUS = 'unexpected_status';
}
