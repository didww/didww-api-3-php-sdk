<?php

namespace Didww\Item;

use Didww\Traits\Fetchable;

class City extends BaseItem
{
    use Fetchable;

    public static function getEndpoint(): string
    {
        return '/cities';
    }

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
