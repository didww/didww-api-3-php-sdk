<?php

namespace Didww\Item\OrderItem;

class AvailableDid extends Did
{
    protected function getCreatableAttributesKeys()
    {
        return ['available_did_id', 'sku_id'];
    }

    public function setAvailableDidId(string $uuid)
    {
        $this->attributes['available_did_id'] = $uuid;
    }

    public function setAvailableDid(\Didww\Item\AvailableDid $availableDid)
    {
        $this->attributes['available_did_id'] = $availableDid->getId();
    }

    public function setSkuId(string $uuid)
    {
        $this->attributes['sku_id'] = $uuid;
    }

    public function setSku(\Didww\Item\StockKeepingUnit $stockKeepingUnit)
    {
        $this->attributes['sku_id'] = $stockKeepingUnit->getId();
    }

    public function setStockKeepingUnitId(string $uuid)
    {
        $this->attributes['sku_id'] = $uuid;
    }

    public function setStockKeepingUnit(\Didww\Item\StockKeepingUnit $stockKeepingUnit)
    {
        $this->attributes['sku_id'] = $stockKeepingUnit->getId();
    }
}
