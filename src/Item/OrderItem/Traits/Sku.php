<?php

namespace Didww\Item\OrderItem\Traits;

trait Sku
{
    public function setSkuId(string $uuid)
    {
        $this->attributes['sku_id'] = $uuid;
    }

    public function getSkuId()
    {
        return $this->attributes['sku_id'];
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
