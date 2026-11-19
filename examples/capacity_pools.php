<?php

// Lists capacity pools with their shared capacity groups and qty-based
// pricings included, then finds one pool by ID.
// 2026-04-16 adds external_reference_id on shared capacity groups.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/capacity_pools.php

require_once 'bootstrap.php';

use Didww\Item\CapacityPool;

echo "=== Capacity Pools ===\n";
$pools = CapacityPool::all(['include' => 'shared_capacity_groups,qty_based_pricings'])->getData();
echo 'Found '.count($pools)." capacity pools\n";

foreach ($pools as $pool) {
    echo "\n".$pool->getName()."\n";
    echo '  ID: '.$pool->getId()."\n";
    echo '  Total channels: '.$pool->getTotalChannelsCount()."\n";
    echo '  Assigned channels: '.$pool->getAssignedChannelsCount()."\n";
    echo '  Renew date: '.($pool->getRenewDate() ?? 'null')."\n";

    $groups = $pool->sharedCapacityGroups()->getIncluded();
    echo '  Shared capacity groups: '.count($groups)."\n";
    foreach ($groups as $group) {
        echo '    - '.$group->getName().' ('.$group->getSharedChannelsCount().' shared, '.$group->getMeteredChannelsCount().' metered)';
        echo ', external reference: '.($group->getExternalReferenceId() ?? 'null')."\n";
    }

    // QtyBasedPricing has no getters: its attributes are read as properties.
    $pricings = $pool->qtyBasedPricings()->getIncluded();
    echo '  Qty-based pricings: '.count($pricings)."\n";
    foreach ($pricings as $pricing) {
        echo '    - qty '.$pricing->qty.': setup '.$pricing->setup_price.', monthly '.$pricing->monthly_price."\n";
    }
}

if (count($pools) > 0) {
    echo "\n=== Find a Capacity Pool ===\n";
    $pool = CapacityPool::find($pools[0]->getId(), ['include' => 'qty_based_pricings'])->getData();
    echo $pool->getName().' ('.$pool->getId().")\n";
    echo '  Setup price: '.$pool->getSetupPrice().', monthly price: '.$pool->getMonthlyPrice().', metered rate: '.$pool->getMeteredRate()."\n";
    echo '  Minimum limit: '.$pool->getMinimumLimit().', minimum qty per order: '.$pool->getMinimumQtyPerOrder()."\n";
    echo '  Qty-based pricings: '.count($pool->qtyBasedPricings()->getIncluded())."\n";
}
