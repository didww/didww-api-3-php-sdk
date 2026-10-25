<?php

namespace Didww\Exception;

/**
 * The reason why a prices service call failed.
 */
enum PricesErrorKind: string
{
    /** The request was refused (400), for example a filter value of the wrong format. */
    case BAD_REQUEST = 'bad_request';

    /** The token was refused again after it was replaced once (401). */
    case NOT_AUTHORIZED = 'not_authorized';

    /** The token does not allow the request (403). */
    case ACCESS_DENIED = 'access_denied';

    /** The account has no price plan for the service (404 price_plan_not_assigned, and any other 404 but price_not_found). */
    case PRICE_PLAN_NOT_ASSIGNED = 'price_plan_not_assigned';

    /** No price matches the lookup (404 price_not_found). */
    case PRICE_NOT_FOUND = 'price_not_found';

    /** The service cannot answer in the requested format (406). */
    case NOT_ACCEPTABLE = 'not_acceptable';

    /**
     * The request budget is spent (429). It is not retried; getRetryAfter()
     * says when to try again.
     */
    case TOO_MANY_REQUESTS = 'too_many_requests';

    /**
     * The service cannot answer yet (503), for example a price file that is
     * still being generated. getRetryAfter() says when to try again.
     */
    case SERVICE_UNAVAILABLE = 'service_unavailable';

    /** A status the call does not expect, such as a redirect, 202 or 204. */
    case UNEXPECTED_STATUS = 'unexpected_status';

    /**
     * The request failed in transport: connection, timeout, TLS or a broken
     * response body. It is not retried.
     */
    case CONNECTION_ERROR = 'connection_error';
}
