<?php

namespace Didww\Item;

use Didww\Traits\Fetchable;

class PublicKey extends BaseItem
{
    use Fetchable;

    protected $type = 'public_keys';

    public function getKey(): string
    {
        return $this->attributes['key'];
    }
}
