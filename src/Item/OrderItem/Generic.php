<?php

namespace Didww\Item\OrderItem;

class Generic extends Base
{
    use Traits\Qty;

    protected function getCreatableAttributesKeys()
    {
        return  [];
    }

    //todo: readonly: getType can't be called
    protected function getType()
    {
        return 'generic_order_items';
    }
}
