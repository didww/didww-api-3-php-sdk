<?php

namespace Didww\Item;

class SharedCapacityGroup extends BaseItem
{
    use \Didww\Traits\Fetchable;
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Deletable;

    protected $type = 'shared_capacity_groups';

    public function setName(string $name)
    {
        $this->attributes['name'] = $name;
    }

    public function getName(): string
    {
        return $this->attributes['name'];
    }

    public function setSharedChannelsCount(int $sharedChannelsCount)
    {
        $this->attributes['shared_channels_count'] = $sharedChannelsCount;
    }

    public function getSharedChannelsCount(): int
    {
        return $this->attributes['shared_channels_count'];
    }

    public function setMeteredChannelsCount(int $sharedMeteredCount)
    {
        $this->attributes['metered_channels_count'] = $sharedMeteredCount;
    }

    public function getMeteredChannelsCount(): int
    {
        return $this->attributes['metered_channels_count'];
    }

    public function getCreatedAt()
    {
        return new \DateTime($this->attributes['created_at']);
    }

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

    protected function getWhiteListAttributesKeys()
    {
        return [
         'name',
         'shared_channels_count',
         'metered_channels_count',
       ];
    }
}
