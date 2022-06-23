<?php

namespace Didww\Item;

class DidGroup extends BaseItem
{
    use \Didww\Traits\Fetchable;

    protected $type = 'did_groups';

    public function stockKeepingUnits()
    {
        return $this->hasMany(StockKeepingUnit::class);
    }

    public function country()
    {
        return $this->hasOne(Country::class);
    }

    public function region()
    {
        return $this->hasOne(Region::class);
    }

    public function city()
    {
        return $this->hasOne(City::class);
    }

    public function didGroupType()
    {
        return $this->hasOne(DidGroupType::class);
    }

    public function getAreaName(): string
    {
        return $this->getAttributes()['area_name'];
    }

    public function getPrefix(): string
    {
        return $this->getAttributes()['prefix'];
    }

    public function getFeatures(): array
    {
        return $this->getAttributes()['features'];
    }

    public function getIsMetered(): bool
    {
        return $this->getAttributes()['is_metered'];
    }

    public function getAllowAdditionalChannels(): bool
    {
        return $this->getAttributes()['allow_additional_channels'];
    }
}
