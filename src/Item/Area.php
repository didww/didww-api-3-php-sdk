<?php

namespace Didww\Item;

use Didww\Traits\Fetchable;

class Area extends BaseItem
{
    use Fetchable;

    protected $type = 'areas';

    public function country()
    {
        return $this->hasOne(Country::class);
    }
}
