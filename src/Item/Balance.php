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

    public static function getEndpoint()
    {
        return '/balance';
    }
}
