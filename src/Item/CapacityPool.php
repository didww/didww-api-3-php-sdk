<?php

namespace Didww\Item;

class CapacityPool extends BaseItem
{
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Fetchable;

    protected $type = 'capacity_pools';

    public function countries()
    {
        return $this->hasMany(Country::class);
    }

    public function sharedCapacityGroups()
    {
        return $this->hasMany(SharedCapacityGroup::class);
    }

    public function qtyBasedPricings()
    {
        return $this->hasMany(QtyBasedPricing::class);
    }
}
