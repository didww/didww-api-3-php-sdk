<?php

namespace Didww\Item;

use Didww\Traits\Fetchable;

/**
 * Immutable ownership-history records for DIDs in the customer's account.
 * Introduced in API 2026-04-16. Records are retained for the last 90 days only.
 *
 * Available filters (server-side):
 *   did_number (eq), action (eq), method (eq),
 *   created_at_gteq, created_at_lteq
 */
class DidHistory extends BaseItem
{
    use Fetchable;

    public static function getEndpoint(): string
    {
        return '/did_history';
    }

    protected $type = 'did_history';

    public function getDidNumber(): string
    {
        return $this->attributes['did_number'];
    }

    public function getAction(): string
    {
        return $this->attributes['action'];
    }

    public function getMethod(): string
    {
        return $this->attributes['method'];
    }

    public function getCreatedAt()
    {
        return $this->dateAttribute('created_at');
    }
}
