<?php

namespace Didww\Item;

class City extends BaseItem
{
    use \Didww\Traits\Fetchable;

    protected $type = 'cities';

    public function country()
    {
        return $this->hasOne(Country::class);
    }

    public function region()
    {
        return $this->hasOne(Region::class);
    }

    public function area()
    {
        return $this->hasOne(Area::class);
    }
}
