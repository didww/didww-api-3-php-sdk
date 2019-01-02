<?php

namespace Didww\Item;

class Region extends BaseItem
{
    use \Didww\Traits\Fetchable;

    protected $type = 'regions';

    public function country()
    {
        return $this->hasOne(Country::class);
    }
}
