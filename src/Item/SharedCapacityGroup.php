<?php

namespace Didww\Item;

class SharedCapacityGroup extends BaseItem
{
    use \Didww\Traits\Fetchable;
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Deletable;

    protected $type = 'shared_capacity_groups';

    public function capacityPool()
    {
        return $this->hasOne(CapacityPool::class);
    }

    public function dids()
    {
        return $this->hasMany(Did::class);
    }


    public function setDids(\Swis\JsonApi\Client\Collection $dids)
    {
        $this->dids()->associate($dids);
    }

    public function setCapacityPool(CapacityPool $capacityPool)
    {
        $this->capacityPool()->associate($capacityPool);
    }
}
