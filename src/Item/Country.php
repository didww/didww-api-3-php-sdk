<?php

namespace Didww\Item;

class Country extends BaseItem
{
    use \Didww\Traits\Fetchable;

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
}
