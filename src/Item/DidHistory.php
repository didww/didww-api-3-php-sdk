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
 *
 * Meta attributes (accessible via the JSON:API meta hash on the returned resource):
 *   meta[from] / meta[to]
 *     Type: integer
 *     Presence: only when action == 'billing_cycles_count_changed'.
 *     Description: The previous (from) and new (to) billing_cycles_count
 *                  values. Absent for every other action.
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
