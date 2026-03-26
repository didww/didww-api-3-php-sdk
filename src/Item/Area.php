<?php

namespace Didww\Item;

use Didww\Traits\Fetchable;

class Area extends BaseItem
{
    use Fetchable;

    public static function getEndpoint(): string
    {
        return '/areas';
    }

    protected $type = 'areas';

    public function country()
    {
        return $this->hasOne(Country::class);
    }
}
