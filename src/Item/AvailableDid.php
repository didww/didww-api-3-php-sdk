<?php

namespace Didww\Item;

use Didww\Traits\Fetchable;

class AvailableDid extends BaseItem
{
    use Fetchable;

    protected $type = 'available_dids';

    public function didGroup()
    {
        return $this->hasOne(DidGroup::class);
    }

    public function nanpaPrefix()
    {
        return $this->hasOne(NanpaPrefix::class);
    }

    public function getNumber(): string
    {
        return $this->attributes['number'];
    }
}
