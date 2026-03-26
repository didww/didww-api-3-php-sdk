<?php

namespace Didww\Item;

use Didww\Traits\Fetchable;

class Pop extends BaseItem
{
    use Fetchable;

    public static function getEndpoint(): string
    {
        return '/pops';
    }

    protected $type = 'pops';

    public function getName()
    {
        return $this->attributes['name'];
    }
}
