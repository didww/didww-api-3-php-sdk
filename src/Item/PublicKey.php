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

    /** @return array [
     * ]
     * 'key' => string // RSA public key in PEM format
     */
    public function getAttributes(): array
    {
        return parent::getAttributes();
    }
}
