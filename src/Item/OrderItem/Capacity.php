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

    public function getCapacityPoolId(): string
    {
        return $this->getAttributes()['capacity_pool_id'];
    }

    public function setCapacityPoolId(string $uuid)
    {
        return $this->attributes['capacity_pool_id'] = $uuid;
    }

    public function setCapacityPool(\Didww\Item\CapacityPool $capacityPool)
    {
        return $this->attributes['capacity_pool_id'] = $capacityPool->getId();
    }

    public function setQty(int $qty)
    {
        $this->attributes['qty'] = $qty;
    }
}
