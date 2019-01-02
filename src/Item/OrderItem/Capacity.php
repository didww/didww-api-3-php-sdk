<?php

namespace Didww\Item\OrderItem;

class Capacity extends Base
{
    protected function getCreatableAttributesKeys()
    {
        return ['capacity_pool_id', 'qty'];
    }

    protected function getType()
    {
        return 'capacity_order_items';
    }
}
