<?php

namespace Didww\Item;

class Did extends BaseItem
{
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Fetchable;

    protected $type = 'dids';

    public function setDedicatedChannelsCount(int $dedicatedChannelCount)
    {
        $this->attributes['dedicated_channels_count'] = $dedicatedChannelCount;
    }

    public function getDedicatedChannelsCount(): int
    {
        return $this->attributes['dedicated_channels_count'];
    }

    public function getTerminated(): boolean
    {
        return $this->attributes['terminated'];
    }

    public function setTerminated(boolean $terminated)
    {
        $this->attributes['terminated'] = $terminated;
    }

    public function setDescription(string $desc)
    {
        $this->attributes['description'] = $desc;
    }

    public function getDescription(): string
    {
        return $this->attributes['description'];
    }

    public function setPendingRemoval(boolean $pendingRemoval)
    {
        $this->attributes['pending_removal'] = $pendingRemoval;
    }

    public function getPendingRemoval(): boolean
    {
        return $this->attributes['pending_removal'];
    }

    public function setCapacityLimit(int $capacityLimit)
    {
        $this->attributes['capacity_limit'] = $capacityLimit;
    }

    public function getCapacityLimit(): int
    {
        return $this->attributes['capacity_limit'];
    }

    public function getBlocked(): boolean
    {
        return $this->attributes['blocked'];
    }

    public function getAwaitingRegistration(): boolean
    {
        return $this->attributes['awaiting_registration'];
    }

    public function getNumber(): boolean
    {
        return $this->attributes['number'];
    }

    public function getCreatedAt()
    {
        return new \DateTime($this->attributes['created_at']);
    }

    public function getExpiresAt()
    {
        return new \DateTime($this->attributes['expires_at']);
    }

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

    protected function getWhiteListAttributesKeys()
    {
        return [
         'pending_removal',
         'capacity_limit',
         'description',
         'terminated',
         'dedicated_channels_count',
       ];
    }
}
