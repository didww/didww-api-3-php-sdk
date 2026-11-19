<?php

// Lists the last ordered DIDs with their 2026-04-16 emergency relationships
// and updates the first one: capacity (shared capacity group, dedicated
// channels, capacity limit) and description. See did_trunk_assignment.php
// for voice routing and did_sms_routing.php for SMS routing.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/dids.php

require_once 'bootstrap.php';

// get the last ordered DIDs (include 2026-04-16 emergency relationships)
$dids = Didww\Item\Did::all([
    'sort' => '-created_at',
    'page' => ['size' => 5, 'number' => 1],
    'include' => 'identity,emergency_calling_service,emergency_verification',
])->getData();
if ($dids->isEmpty()) {
    exit("No DIDs found. Please order a DID first.\n");
}

foreach ($dids as $listed) {
    echo 'DID '.$listed->getNumber().' ('.$listed->getId().")\n";
    echo '  Emergency enabled: '.($listed->getEmergencyEnabled() ? 'true' : 'false')."\n";
    $ecs = $listed->emergencyCallingService()->getIncluded();
    if ($ecs) {
        echo '  Emergency Calling Service: '.$ecs->getId()."\n";
    }
    $ev = $listed->emergencyVerification()->getIncluded();
    if ($ev) {
        echo '  Emergency Verification: '.$ev->getId()."\n";
    }
    $identity = $listed->identity()->getIncluded();
    if ($identity) {
        echo '  Identity: '.$identity->getId()."\n";
    }
}

$did = $dids[0];
echo "\nUpdating DID ".$did->getNumber()."\n";

// assign capacity
$capacityPools = Didww\Item\CapacityPool::all()->getData()->all();

$supportedPools = array_filter($capacityPools, function ($v, $k) {
    return 'Extended' == $v->getName();
}, ARRAY_FILTER_USE_BOTH);
$supportedPool = reset($supportedPools);

if ($supportedPool) {
    $sharedCapacityGroups = Didww\Item\SharedCapacityGroup::all(['filter' => ['capacity_pool.id' => $supportedPool->getId()]])->getData();
    if ($sharedCapacityGroups->isNotEmpty()) {
        // assign channel group to use shared channels
        $did->setSharedCapacityGroup($sharedCapacityGroups[0]);
    }
    // assign channel pool to use dedicated channels
    $did->setCapacityPool($supportedPool);
    $did->setDedicatedChannelsCount(1);
}

$did->setCapacityLimit(5); // allow 5 simultenious calls

// set custom text description
$did->setDescription('Hi');

$didDocument = $did->save();

if ($didDocument->hasErrors()) {
    var_dump($didDocument->getErrors());
} else {
    $did = $didDocument->getData();
    var_dump(
        $did->getId(), // 1f6fc2bd-f081-4202-9b1a-d9cb88d942b9
        $did->getDescription(), // "Hi"
        $did->getCreatedAt(), // object(DateTime)
        $did->getExpiresAt(), // object(DateTime)
        $did->getCapacityLimit(), // int(5)
        $did->getDedicatedChannelsCount() // int(1)
    );
}
