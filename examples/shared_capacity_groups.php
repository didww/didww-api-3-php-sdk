<?php

// Lists shared capacity groups in capacity pools.
// 2026-04-16 adds external_reference_id for customer tagging.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/shared_capacity_groups.php

require_once 'bootstrap.php';

use Didww\Item\CapacityPool;
use Didww\Item\SharedCapacityGroup;

// List shared capacity groups
echo "=== Existing Shared Capacity Groups ===\n";
$document = SharedCapacityGroup::all(['include' => 'capacity_pool']);
$sharedGroups = $document->getData();
echo 'Found '.count($sharedGroups)." shared capacity groups\n";

foreach ($sharedGroups->take(10) as $group) {
    echo "\nGroup: ".$group->getName()."\n";
    echo '  ID: '.$group->getId()."\n";
    echo '  Shared channels: '.$group->getSharedChannelsCount()."\n";
    echo '  Metered channels: '.$group->getMeteredChannelsCount()."\n";
    $extRef = $group->getExternalReferenceId();
    if ($extRef) {
        echo '  External reference: '.$extRef."\n";
    }
    $capacityPool = $group->capacityPool()->getIncluded();
    if ($capacityPool) {
        echo '  Capacity pool: '.$capacityPool->getName()."\n";
    }
}

// Get a specific capacity pool and show its shared capacity groups
echo "\n=== Capacity Pool with Shared Groups ===\n";
$poolDocument = CapacityPool::all(['include' => 'shared_capacity_groups']);
$capacityPools = $poolDocument->getData();

if (!empty($capacityPools)) {
    $pool = $capacityPools[0];
    echo 'Capacity Pool: '.$pool->getName()."\n";
    $groups = $pool->sharedCapacityGroups()->getIncluded();
    if ($groups && count($groups->all()) > 0) {
        echo '  Total channels: '.$pool->getTotalChannelsCount()."\n";
        echo '  Assigned channels: '.$pool->getAssignedChannelsCount()."\n";
        echo "  Shared Capacity Groups:\n";
        foreach ($groups->all() as $group) {
            echo '    - '.$group->getName().' ('.$group->getSharedChannelsCount().' shared, '.$group->getMeteredChannelsCount()." metered)\n";
        }
    } else {
        echo "  No shared capacity groups\n";
    }
}
