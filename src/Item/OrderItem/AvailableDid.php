<?php

namespace Didww\Item\OrderItem;

class AvailableDid extends AbstractReferencedDidOrderItem
{
    protected function getReferencedIdKey(): string
    {
        return 'available_did_id';
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
