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
     * Get DIDWW API 3 endpoint
     *
     * @return string
     */
    public static function getEndpoint(): string
    {
        return '/balance';
    }

    /**
     * Get account total balance (including credit line balance)
     *
     * @return float
     */
    public function getTotalBalance(): float
    {
        return (float)$this->getAttributes()['total_balance'];
    }

    /**
     * Get account credit line balance
     *
     * @return float
     */
    public function getCredit(): float
    {
        return (double)$this->getAttributes()['credit'];
    }

    /**
     * Get account balance (not including credit line balance)
     *
     * @return float
     */
    public function getBalance(): float
    {
        return (float)$this->getAttributes()['balance'];
    }
}
