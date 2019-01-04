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

    public function getName(): string
    {
        return $this->getAttributes()['name'];
    }

    public function getRenewDate(): \Date
    {
        return new \Date($this->getAttributes()['renew_date']);
    }

    public function getTotalChannelsCount(): int
    {
        return $this->getAttributes()['total_channels_count'];
    }

    public function getAssignedChannelsCount(): int
    {
        return $this->getAttributes()['assigned_channels_count'];
    }

    public function getMinimumLimit(): int
    {
        return $this->getAttributes()['minimum_limit'];
    }

    public function getMinimumQtyPerOrder(): int
    {
        return $this->getAttributes()['minimum_qty_per_order'];
    }

    public function getSetupPrice(): float
    {
        return (float) $this->getAttributes()['setup_price'];
    }

    public function getMonthlyPrice(): float
    {
        return (float) $this->getAttributes()['monthly_price'];
    }

    public function getMeteredRate(): float
    {
        return (float) $this->getAttributes()['metered_rate'];
    }

    public function setTotalChannelsCount(int $totalChannelsCount)
    {
        $this->attributes['total_channels_count'] = $totalChannelsCount;
    }

    protected function getWhiteListAttributesKeys()
    {
        return [
         'total_channels_count',
       ];
    }
}
