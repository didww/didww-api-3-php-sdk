<?php

namespace Didww;

class TypeMapper extends \Swis\JsonApi\Client\TypeMapper
{
    public function registerItems()
    {
        $this->setMapping('countries', Item\Country::class);
        $this->setMapping('regions', Item\Region::class);
        $this->setMapping('orders', Item\Order::class);
        $this->setMapping('balances', Item\Balance::class);
        $this->setMapping('dids', Item\Did::class);
        $this->setMapping('did_groups', Item\DidGroup::class);
        $this->setMapping('trunks', Item\Trunk::class);
        $this->setMapping('trunk_groups', Item\TrunkGroup::class);
        $this->setMapping('capacity_pools', Item\CapacityPool::class);
        $this->setMapping('shared_capacity_groups', Item\SharedCapacityGroup::class);
        $this->setMapping('available_dids', Item\AvailableDid::class);
        $this->setMapping('stock_keeping_units', Item\StockKeepingUnit::class);
        $this->setMapping('did_reservations', Item\DidReservation::class);
        $this->setMapping('pops', Item\Pop::class);
        $this->setMapping('cities', Item\City::class);
        $this->setMapping('did_group_types', Item\DidGroupType::class);
        $this->setMapping('qty_based_pricings', Item\QtyBasedPricing::class);
        $this->setMapping('cdr_exports', Item\CdrExport::class);
    }
}
