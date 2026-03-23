<?php

namespace Didww\Item;

use Didww\Traits\Fetchable;

class Pop extends BaseItem
{
    use Fetchable;

    protected $type = 'pops';

    public function getName()
    {
        return $this->attributes['name'];
    }
}
