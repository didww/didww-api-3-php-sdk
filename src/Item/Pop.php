<?php

namespace Didww\Item;

class Pop extends BaseItem
{
    use \Didww\Traits\Fetchable;

    protected $type = 'pops';

    public function getName()
    {
        return $this->attributes['name'];
    }
}
