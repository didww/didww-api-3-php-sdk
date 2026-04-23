<?php

// End-to-end Emergency Calling Service purchase flow (2026-04-16).
// Steps: order DID -> get emergency requirements -> find identity ->
// find address -> validate -> create verification -> check status -> get service.

require_once 'bootstrap.php';

use Didww\Item\Address;
use Didww\Item\AvailableDid;
use Didww\Item\Did;
use Didww\Item\EmergencyRequirement;
use Didww\Item\EmergencyRequirementValidation;
use Didww\Item\EmergencyVerification;
use Didww\Item\Identity;
use Didww\Item\Order;
use Didww\Item\OrderItem\AvailableDid as AvailableDidOrderItem;

// Step 0: Order a specific available DID with emergency feature
echo "=== Step 0: Order an available DID with emergency feature ===\n";

// Find address first to determine country
$addresses = Address::all(['include' => 'country', 'page' => ['size' => 1, 'number' => 1]])->getData();
if (0 === count($addresses)) {
    exit("No addresses on this account. Please create an address first.\n");
}
$address = $addresses[0];
$addressCountry = $address->country()->getIncluded();
echo '  Address country: '.$addressCountry->getName()."\n";

// Find an available DID with emergency feature in that country
$availableDids = AvailableDid::all([
    'filter' => [
        'did_group.features' => 'emergency',
        'country.id' => $addressCountry->getId(),
    ],
    'include' => 'did_group,did_group.stock_keeping_units',
    'page' => ['size' => 1, 'number' => 1],
])->getData();

if (0 === count($availableDids)) {
    exit('No available DIDs with emergency feature in '.$addressCountry->getName().".\n");
}

$availableDid = $availableDids[0];
$didGroup = $availableDid->didGroup()->getIncluded();
$skus = $didGroup->stockKeepingUnits()->getIncluded();
$sku = $skus->first();
if (!$sku) {
    exit("No SKU found for DID group.\n");
}

echo '  Available DID: '.$availableDid->getNumber()."\n";
echo '  DID Group: '.($didGroup->getAreaName() ?? $didGroup->getId())."\n";

$didItem = new AvailableDidOrderItem(['available_did_id' => $availableDid->getId(), 'sku_id' => $sku->getId()]);
$order = new Order();
$order->setItems([$didItem]);

$orderDocument = $order->save();
if ($orderDocument->hasErrors()) {
    exit("Failed to order DID.\n");
}
$order = $orderDocument->getData();
echo '  Order: '.$order->getId().' — '.$order->getStatus()->value."\n";

// Wait for order to complete
for ($i = 0; $i < 10; ++$i) {
    if ('completed' === $order->getStatus()->value) {
        break;
    }
    sleep(5);
    $order = Order::find($order->getId())->getData();
}
if ('completed' !== $order->getStatus()->value) {
    exit('  Order did not complete (status: '.$order->getStatus()->value.").\n");
}
echo "  Order completed\n";

// Step 1: Find the newly ordered DID
echo "\n=== Step 1: Find the newly ordered DID ===\n";
$dids = Did::all([
    'filter' => [
        'did_group.features' => 'emergency',
        'emergency_enabled' => 'false',
    ],
    'include' => 'did_group,did_group.country,did_group.did_group_type,emergency_calling_service',
    'sort' => '-created_at',
    'page' => ['size' => 10, 'number' => 1],
])->getData();

// Pick the first DID that has no emergency_calling_service yet
$selectedDid = null;
foreach ($dids as $d) {
    if (!$d->emergencyCallingService()->getIncluded()) {
        $selectedDid = $d;
        break;
    }
}

if (!$selectedDid) {
    exit("No available DID without an existing Emergency Calling Service.\n");
}
$didGroup = $selectedDid->didGroup()->getIncluded();
$country = $didGroup ? $didGroup->country()->getIncluded() : null;
$didGroupType = $didGroup ? $didGroup->didGroupType()->getIncluded() : null;

echo 'DID: '.$selectedDid->getNumber().' ('.$selectedDid->getId().")\n";
echo '  Country: '.($country ? $country->getName() : 'N/A')."\n";
echo '  DID Group Type: '.($didGroupType ? $didGroupType->name : 'N/A')."\n";

// Step 2: Get emergency requirements for this country + did_group_type
echo "\n=== Step 2: Get Emergency Requirements ===\n";
$requirements = EmergencyRequirement::all([
    'filter' => [
        'country.id' => $country->getId(),
        'did_group_type.id' => $didGroupType->getId(),
    ],
])->getData();

if (0 === count($requirements)) {
    exit("No emergency requirements found for this country/did_group_type.\n");
}

$emergencyRequirement = $requirements[0];
echo 'Requirement: '.$emergencyRequirement->getId()."\n";
echo '  Identity type: '.$emergencyRequirement->getIdentityType()."\n";
echo '  Estimate setup time: '.($emergencyRequirement->getEstimateSetupTime() ?? 'N/A')."\n";
$meta = $emergencyRequirement->getMeta();
if ($meta) {
    if (isset($meta['setup_price'])) {
        echo '  Setup price: '.$meta['setup_price']."\n";
    }
    if (isset($meta['monthly_price'])) {
        echo '  Monthly price: '.$meta['monthly_price']."\n";
    }
}

// Step 3: Find an identity
echo "\n=== Step 3: Find Identity ===\n";
$identities = Identity::all(['page' => ['size' => 1, 'number' => 1]])->getData();

if (0 === count($identities)) {
    exit("No identities available. Please create an identity first.\n");
}

$identity = $identities[0];
echo 'Identity: '.$identity->getId()."\n";
echo '  Name: '.$identity->getFirstName().' '.$identity->getLastName()."\n";

// Step 4: Use the address we found earlier
echo "\n=== Step 4: Using Address ===\n";
echo 'Address: '.$address->getId()."\n";
echo '  City: '.$address->getCityName()."\n";

// Step 5: Validate the emergency requirement
echo "\n=== Step 5: Validate Emergency Requirement ===\n";
$validation = new EmergencyRequirementValidation();
$validation->setEmergencyRequirement(EmergencyRequirement::build($emergencyRequirement->getId()));
$validation->setAddress(Address::build($address->getId()));
$validation->setIdentity(Identity::build($identity->getId()));

$validationDocument = $validation->save();

if ($validationDocument->hasErrors()) {
    echo "Validation failed:\n";
    foreach ($validationDocument->getErrors() as $error) {
        echo '  - '.$error->getDetail()."\n";
    }
    exit("Cannot proceed without valid requirement.\n");
}
echo "Validation passed!\n";

// Step 6: Create emergency verification
echo "\n=== Step 6: Create Emergency Verification ===\n";
$suffix = bin2hex(random_bytes(4));
$verification = new EmergencyVerification();
$verification->setCallbackUrl('https://example.com/callbacks/emergency');
$verification->setCallbackMethod('post');
$verification->setExternalReferenceId("php-scenario-$suffix");
$verification->setAddress(Address::build($address->getId()));
$verification->setDids(new Swis\JsonApi\Client\Collection([Did::build($selectedDid->getId())]));

$verificationDocument = $verification->save();

if ($verificationDocument->hasErrors()) {
    echo "Verification creation failed:\n";
    foreach ($verificationDocument->getErrors() as $error) {
        echo '  - '.$error->getDetail()."\n";
    }
    exit("Cannot create emergency verification.\n");
}

$verification = $verificationDocument->getData();
echo 'Created verification: '.$verification->getId()."\n";
echo '  Reference: '.($verification->getReference() ?? 'N/A')."\n";
echo '  Status: '.$verification->getStatus()->value."\n";
echo '  External Reference: '.$verification->getExternalReferenceId()."\n";

// Step 7: Check verification status
echo "\n=== Step 7: Check Verification Status ===\n";
$freshDocument = EmergencyVerification::find($verification->getId(), [
    'include' => 'address,emergency_calling_service',
]);
$freshVerification = $freshDocument->getData();
echo 'Status: '.$freshVerification->getStatus()->value."\n";

// Step 8: Get Emergency Calling Service
echo "\n=== Step 8: Get Emergency Calling Service ===\n";
$ecs = $freshVerification->emergencyCallingService()->getIncluded();
if ($ecs) {
    echo 'ECS: '.$ecs->getId()."\n";
    echo '  Name: '.$ecs->getName()."\n";
    echo '  Status: '.$ecs->getStatus()->value."\n";
    $ecsMeta = $ecs->getMeta();
    if ($ecsMeta) {
        if (isset($ecsMeta['setup_price'])) {
            echo '  Setup price: '.$ecsMeta['setup_price']."\n";
        }
        if (isset($ecsMeta['monthly_price'])) {
            echo '  Monthly price: '.$ecsMeta['monthly_price']."\n";
        }
    }
} else {
    echo "No ECS linked yet (verification may still be pending).\n";
}

echo "\nDone!\n";
