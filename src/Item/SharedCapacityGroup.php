<?php

namespace Didww\Item;

use Didww\Traits\Deletable;
use Didww\Traits\Fetchable;
use Didww\Traits\HasExternalReferenceId;
use Didww\Traits\Saveable;

class SharedCapacityGroup extends BaseItem
{
    use Fetchable;
    use Saveable;
    use Deletable;
    use HasExternalReferenceId;

    public static function getEndpoint(): string
    {
        return '/shared_capacity_groups';
    }

    protected $type = 'shared_capacity_groups';

    protected $visible = [
        'name',
        'shared_channels_count',
        'metered_channels_count',
        'external_reference_id',
    ];

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
        return $this->dateAttribute('created_at');
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
}
