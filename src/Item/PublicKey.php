<?php

namespace Didww\Item;

class PublicKey extends BaseItem
{
    use \Didww\Traits\Fetchable;

    protected $type = 'public_keys';

    public function getKey(): string
    {
        return $this->attributes['key'];
    }
}
