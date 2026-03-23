<?php

namespace Didww\Item;

use Didww\Traits\Fetchable;

class Region extends BaseItem
{
    use Fetchable;

    protected $type = 'regions';

    public function country()
    {
        return $this->hasOne(Country::class);
    }

    /**
     * Get name of the region.
     */
    public function getName(): string
    {
        return $this->getAttributes()['name'];
    }

    /**
     * Get ISO of the region.
     */
    public function getIso(): ?string
    {
        return $this->attribute('iso');
    }
}
