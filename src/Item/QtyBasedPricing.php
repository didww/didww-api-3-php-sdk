<?php

namespace Didww\Item;

class QtyBasedPricing extends BaseItem
{
    public static function getEndpoint(): string
    {
        return '/qty_based_pricings';
    }

    protected $type = 'qty_based_pricings';
}
