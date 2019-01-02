<?php

namespace Didww\Item\OrderItem;

class AvailableDid extends Did
{
    protected function getCreatableAttributesKeys()
    {
        return ['available_did_id', 'sku_id'];
    }
}
