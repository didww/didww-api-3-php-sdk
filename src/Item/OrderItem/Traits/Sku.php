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
        $this->setSkuId($stockKeepingUnit->getId());
    }
}
