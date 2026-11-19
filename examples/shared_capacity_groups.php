<?php

// Shared capacity groups: creates a group in a capacity pool, lists the
// groups of that pool, updates the group and deletes it, also when a step
// fails. Shared channels come from the channels purchased in the pool (see
// orders_capacity.php); metered channels are billed per minute.
// 2026-04-16 adds external_reference_id for customer tagging.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/shared_capacity_groups.php

require_once 'bootstrap.php';

use Didww\Item\CapacityPool;
use Didww\Item\SharedCapacityGroup;

function printErrors(string $message, $document): void
{
    echo $message.":\n";
    foreach ($document->getErrors() as $error) {
        echo '  - '.($error->getDetail() ?? $error->getTitle())."\n";
    }
}

function printGroup(SharedCapacityGroup $group): void
{
    echo '  ID: '.$group->getId()."\n";
    echo '  Name: '.$group->getName()."\n";
    echo '  Shared channels: '.$group->getSharedChannelsCount()."\n";
    echo '  Metered channels: '.$group->getMeteredChannelsCount()."\n";
    echo '  External reference: '.($group->getExternalReferenceId() ?? 'null')."\n";
}

// The group takes one shared channel: use a pool with an unassigned channel
$pool = CapacityPool::all()->getData()->first(
    fn (CapacityPool $candidate) => $candidate->getTotalChannelsCount() > $candidate->getAssignedChannelsCount()
);
if (null === $pool) {
    echo "No capacity pool has an unassigned channel; see orders_capacity.php to buy one.\n";
    exit(1);
}
echo 'Using capacity pool: '.$pool->getName().' ('.$pool->getId().")\n";

// set name (should be unique)
$suffix = bin2hex(random_bytes(4));

echo "\n=== Creating Shared Capacity Group ===\n";
$group = new SharedCapacityGroup();
$group->setName("PHP Channel Group $suffix");
$group->setMeteredChannelsCount(10);
// see orders_capacity.php to purchase shared channels in the pool
$group->setSharedChannelsCount(1);
// set capacity pool as a source of purchased channels
$group->setCapacityPool(CapacityPool::build($pool->getId()));
$group->setExternalReferenceId("php-scg-$suffix");

$document = $group->save();
if ($document->hasErrors()) {
    printErrors('Error creating shared capacity group', $document);
    exit(1);
}
$group = $document->getData();

try {
    printGroup($group);

    echo "\n=== Shared Capacity Groups of the Pool ===\n";
    $groups = SharedCapacityGroup::all([
        'filter' => ['capacity_pool.id' => $pool->getId()],
        'include' => 'capacity_pool',
    ])->getData();
    foreach ($groups as $listed) {
        $listedPool = $listed->capacityPool()->getIncluded();
        echo '  '.$listed->getName().' ('.$listed->getSharedChannelsCount().' shared, '.$listed->getMeteredChannelsCount().' metered)';
        echo ' in '.($listedPool ? $listedPool->getName() : 'unknown pool')."\n";
    }

    echo "\n=== Updating Shared Capacity Group ===\n";
    // Only the changed attributes are sent.
    $group->setName("PHP Channel Group $suffix (updated)");
    $group->setMeteredChannelsCount(5);
    $document = $group->save();
    if ($document->hasErrors()) {
        printErrors('Error updating shared capacity group', $document);
    } else {
        printGroup($document->getData());
    }
} finally {
    echo "\n=== Deleting Shared Capacity Group ===\n";
    $document = $group->delete();
    if ($document->hasErrors()) {
        printErrors('Error deleting shared capacity group '.$group->getId(), $document);
    } else {
        echo "Shared capacity group deleted\n";
    }
}
