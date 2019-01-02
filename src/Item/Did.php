<?php

namespace Didww\Item;

class Did extends BaseItem
{
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Fetchable;

    protected $type = 'dids';

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    public function didGroup()
    {
        return $this->hasOne(DidGroup::class);
    }

    public function trunk()
    {
        return $this->hasOne(Trunk::class);
    }

    public function trunkGroup()
    {
        return $this->hasOne(TrunkGroup::class);
    }

    public function capacityPool()
    {
        return $this->hasOne(CapacityPool::class);
    }

    public function sharedCapacityGroup()
    {
        return $this->hasOne(SharedCapacityGroup::class);
    }

    public function setTrunkGroup(TrunkGroup $trunkGroup)
    {
        $this->trunkGroup()->associate($trunkGroup);
        $this->trunk()->dissociate();
    }

    public function setTrunk(Trunk $trunk)
    {
        $this->trunk()->associate($trunk);
        $this->trunkGroup()->dissociate();
    }

    public function setCapacityPool(CapacityPool $capacityPool)
    {
        $this->capacityPool()->associate($capacityPool);
    }

    public function setSharedCapacityGroup(SharedCapacityGroup $sharedCapacityGroup)
    {
        $this->sharedCapacityGroup()->associate($sharedCapacityGroup);
    }
}
