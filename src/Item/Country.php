<?php

namespace Didww\Item;

use Didww\Traits\Fetchable;

class Country extends BaseItem
{
    use Fetchable;

    protected $type = 'countries';

    public function getName(): string
    {
        return $this->getAttributes()['name'];
    }

    public function getPrefix(): string
    {
        return $this->getAttributes()['prefix'];
    }

    public function getIso(): string
    {
        return $this->getAttributes()['iso'];
    }

    public function regions()
    {
        return $this->hasMany(Region::class);
    }
}
