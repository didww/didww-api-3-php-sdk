<?php

require_once 'bootstrap.php';

// get last ordered DID (include 2026-04-16 emergency relationships)
$did = Didww\Item\Did::all([
    'sort' => '-created_at',
    'page' => ['size' => 1, 'number' => 1],
    'include' => 'identity,emergency_calling_service,emergency_verification',
])->getData()[0];

echo "Selected DID: " . $did->getId() . "\n";
echo "  Number: " . $did->getNumber() . "\n";
echo "  Emergency enabled: " . ($did->getEmergencyEnabled() ? 'true' : 'false') . "\n";
$ecs = $did->emergencyCallingService()->getIncluded();
if ($ecs) {
    echo "  Emergency Calling Service: " . $ecs->getId() . "\n";
}
$ev = $did->emergencyVerification()->getIncluded();
if ($ev) {
    echo "  Emergency Verification: " . $ev->getId() . "\n";
}
$identity = $did->identity()->getIncluded();
if ($identity) {
    echo "  Identity: " . $identity->getId() . "\n";
}

// see trunks example to create Trunk
// get last SIP trunk
$trunk = Didww\Item\VoiceInTrunk::all(['sort' => '-created_at', 'filter' => ['configuration.type' => 'sip_configurations']])->getData()[0];

// assign trunk
$did->setVoiceInTrunk($trunk);
$didDocument = $did->save(['include' => 'voice_in_trunk']);

if ($didDocument->hasErrors()) {
    var_dump($didDocument->getErrors());
} else {
    $trunk = $didDocument->getData()->voiceInTrunk()->getIncluded();
    var_dump(
        $trunk->getId(), // 1f6fc2bd-f081-4202-9b1a-d9cb88d942b9
        $trunk->getName(), // "My New Custom Sip Trunk 5c2e393794b07"
        $trunk->getCreatedAt(), // object(DateTime)
        $trunk->getRingingTimeout() // int(30)
    );
}

// assign capacity
$capacityPools = Didww\Item\CapacityPool::all()->getData()->all();

$supportedPools = array_filter($capacityPools, function ($v, $k) {
    return 'Extended' == $v->getName();
}, ARRAY_FILTER_USE_BOTH);
$supportedPool = reset($supportedPools);

$sharedCapacityGroup = Didww\Item\SharedCapacityGroup::all(['filters' => ['capacity_pool.id' => $supportedPool->getId()]])->getData()[0];
// assign channel group to use shared channels
$did->setSharedCapacityGroup($sharedCapacityGroup);
// assign channel pool to use dedicated channels
$did->setCapacityPool($supportedPool);
$did->setDedicatedChannelsCount(1);

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
