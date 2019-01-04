<?php

namespace Didww\Item\OrderItem;

class ReservationDid extends Did
{
    protected function getCreatableAttributesKeys()
    {
        return ['did_reservation_id', 'sku_id'];
    }

    public function setDidReservationId(string $uuid)
    {
        $this->attributes['did_reservation_id'] = $uuid;
    }

    public function setDidReservation(\Didww\Item\DidReservation $didReservation)
    {
        $this->attributes['did_reservation_id'] = $didReservation->getId();
    }

    public function setSkuId(string $uuid)
    {
        $this->attributes['sku_id'] = $uuid;
    }

    public function setSku(\Didww\Item\StockKeepingUnit $sku)
    {
        $this->attributes['sku_id'] = $sku->getId();
    }

    // aliases
    public function setStockKeepingUnitId(string $uuid)
    {
        $this->setSkuId($uuid);
    }

    public function setStockKeepingUnit(\Didww\Item\StockKeepingUnit $sku)
    {
        $this->setSku($sku);
    }
}
