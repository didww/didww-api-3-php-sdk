<?php

namespace Didww\Item;

class StockKeepingUnit extends BaseItem
{
    protected $type = 'stock_keeping_units';

    public function getSetupPrice(): float
    {
        return (float)$this->getAttributes()['setup_price'];
    }

    public function getMonthlyPrice(): float
    {
        return (float)$this->getAttributes()['monthly_price'];
    }

    public function getChannelsIncludedCount()
    {
        return $this->getAttributes()['channels_included_count'];
    }
}
