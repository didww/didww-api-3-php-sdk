<?php

namespace Didww\Item;

class Balance extends BaseItem
{
    protected $type = 'balances';

    // $uuid for singular resource is not needed
    public static function find(string $uuid = null, array $parameters = [])
    {
        return self::getRepository()->take($parameters);
    }

    /**
     * Get DIDWW API 3 endpoint.
     */
    public static function getEndpoint(): string
    {
        return '/balance';
    }

    /**
     * Get account total balance (including credit line balance).
     */
    public function getTotalBalance(): float
    {
        return (float) $this->attributes['total_balance'];
    }

    /**
     * Get account credit line balance.
     */
    public function getCredit(): float
    {
        return (float) $this->attributes['credit'];
    }

    /**
     * Get account balance (not including credit line balance).
     */
    public function getBalance(): float
    {
        return (float) $this->attributes['balance'];
    }
}
