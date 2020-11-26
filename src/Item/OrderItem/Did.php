<?php

namespace Didww\Item\OrderItem;

class Did extends Base
{
    use Traits\Sku;
    use Traits\Qty;

    protected function getCreatableAttributesKeys()
    {
        return ['sku_id', 'qty'];
    }

    public function getType(): string
    {
        return 'did_order_items';
    }

    public function getDidGroupId()
    {
        return $this->getAttributes()['did_group_id'];
    }
}
