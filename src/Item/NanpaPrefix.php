<?php

namespace Didww\Item;

class NanpaPrefix extends BaseItem
{
    use \Didww\Traits\Fetchable;

    protected $type = 'nanpa_prefixes';

    public function country()
    {
        return $this->hasOne(Country::class);
    }

    public function region()
    {
        return $this->hasOne(Region::class);
    }

    public function getNPA(): string
    {
        return $this->getAttributes()['npa'];
    }

    public function getNXX(): string
    {
        return $this->getAttributes()['nxx'];
    }
}
