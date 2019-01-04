<?php

namespace Didww\Item;

class AvailableDid extends BaseItem
{
    use \Didww\Traits\Fetchable;

    protected $type = 'available_dids';

    public function didGroup()
    {
        return $this->hasOne(DidGroup::class);
    }

    public function getNumber(): string
    {
        return $this->getAttributes()['number'];
    }
}
