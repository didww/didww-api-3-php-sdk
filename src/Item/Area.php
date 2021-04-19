<?php

namespace Didww\Item;

class Area extends BaseItem
{
    use \Didww\Traits\Fetchable;

    protected $type = 'areas';

    public function country()
    {
        return $this->hasOne(Country::class);
    }
}
