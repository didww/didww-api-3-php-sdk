<?php

namespace Didww\Item\OrderItem;

class ReservationDid extends Did
{
    use Traits\Sku;

    protected function getCreatableAttributesKeys()
    {
        return $this->withOptionalKeys(['did_reservation_id', 'sku_id']);
    }

    public function setDidReservationId(string $uuid)
    {
        $this->attributes['did_reservation_id'] = $uuid;
    }

    public function setDidReservation(\Didww\Item\DidReservation $didReservation)
    {
        $this->attributes['did_reservation_id'] = $didReservation->getId();
    }
}
