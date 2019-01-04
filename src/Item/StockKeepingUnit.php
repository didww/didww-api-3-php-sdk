<?php

namespace Didww\Item;

class StockKeepingUnit extends BaseItem
{
    protected $type = 'stock_keeping_units';

    public function getSetupPrice(): float
    {
        return (float) $this->attributes['setup_price'];
    }

    public function getMonthlyPrice(): float
    {
        return (float) $this->attributes['monthly_price'];
    }

    public function getChannelsIncludedCount()
    {
        return $this->attributes['channels_included_count'];
    }
}
