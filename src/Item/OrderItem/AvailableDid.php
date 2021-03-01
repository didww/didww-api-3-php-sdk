<?php

namespace Didww\Item\OrderItem;

class AvailableDid extends Did
{
    use Traits\Sku;

    protected function getCreatableAttributesKeys()
    {
        return $this->withOptionalKeys(['available_did_id', 'sku_id']);
    }

    public function setAvailableDidId(string $uuid)
    {
        $this->attributes['available_did_id'] = $uuid;
    }

    public function setAvailableDid(\Didww\Item\AvailableDid $availableDid)
    {
        $this->attributes['available_did_id'] = $availableDid->getId();
    }
}
