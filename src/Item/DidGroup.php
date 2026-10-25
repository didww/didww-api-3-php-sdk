<?php

namespace Didww\Item;

use Didww\Enum\Feature;
use Didww\Enum\NumberType;
use Didww\Traits\Fetchable;

class DidGroup extends BaseItem
{
    use Fetchable;

    public static function getEndpoint(): string
    {
        return '/did_groups';
    }

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

    public function getNumberType(): NumberType|string|null
    {
        return $this->enumAttribute('number_type', NumberType::class);
    }

    public function addressRequirement()
    {
        return $this->hasOne(AddressRequirement::class);
    }

    public function getAreaName(): string
    {
        return $this->getAttributes()['area_name'];
    }

    public function getPrefix(): string
    {
        return $this->getAttributes()['prefix'];
    }

    /**
     * @return Feature[]
     */
    public function getFeatures(): array
    {
        return $this->enumArrayAttribute('features', Feature::class) ?? [];
    }

    public function getIsMetered(): bool
    {
        return $this->getAttributes()['is_metered'];
    }

    public function getIsDemo(): bool
    {
        return $this->getAttributes()['is_demo'];
    }

    public function getAllowAdditionalChannels(): bool
    {
        return $this->getAttributes()['allow_additional_channels'];
    }

    public function getServiceRestrictions(): ?string
    {
        return $this->attribute('service_restrictions');
    }
}
