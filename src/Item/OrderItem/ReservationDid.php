<?php

namespace Didww\Item\OrderItem;

class ReservationDid extends Did
{
    protected function getCreatableAttributesKeys()
    {
        return ['did_reservation_id', 'sku_id'];
    }
}
