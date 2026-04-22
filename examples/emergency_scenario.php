<?php

// End-to-end Emergency Calling Service purchase flow (2026-04-16).
// Steps: find DID with emergency feature -> get emergency requirements ->
// find identity -> find address -> validate -> create verification -> check status -> get service.

require_once 'bootstrap.php';

use Didww\Enum\Feature;
use Didww\Item\Address;
use Didww\Item\Did;
use Didww\Item\EmergencyCallingService;
use Didww\Item\EmergencyRequirement;
use Didww\Item\EmergencyRequirementValidation;
use Didww\Item\EmergencyVerification;
use Didww\Item\Identity;

// Step 1: Find a DID with emergency feature enabled
echo "=== Step 1: Find DID with Emergency Feature ===\n";
$dids = Did::all([
    'include' => 'did_group,emergency_calling_service,emergency_verification',
    'page' => ['size' => 10, 'number' => 1],
])->getData();

$selectedDid = null;
foreach ($dids as $did) {
    if ($did->getEmergencyEnabled()) {
        $selectedDid = $did;
        break;
    }
}

if (!$selectedDid) {
    echo "No DID with emergency feature found. Listing first available DID instead.\n";
    if (count($dids) > 0) {
        $selectedDid = $dids[0];
    } else {
        exit("No DIDs available on this account.\n");
    }
}

echo 'Selected DID: '.$selectedDid->getId()."\n";
echo '  Number: '.$selectedDid->getNumber()."\n";
echo '  Emergency enabled: '.($selectedDid->getEmergencyEnabled() ? 'true' : 'false')."\n";

$existingEcs = $selectedDid->emergencyCallingService()->getIncluded();
if ($existingEcs) {
    echo '  Already has Emergency Calling Service: '.$existingEcs->getId()."\n";
}

// Step 2: Get emergency requirements
echo "\n=== Step 2: Get Emergency Requirements ===\n";
$document = EmergencyRequirement::all(['include' => 'country,did_group_type']);
$requirements = $document->getData();
echo 'Found '.count($requirements)." emergency requirements\n";

if (0 === count($requirements)) {
    exit("No emergency requirements available.\n");
}

$emergencyRequirement = $requirements[0];
echo 'Using requirement: '.$emergencyRequirement->getId()."\n";
echo '  Identity type: '.$emergencyRequirement->getIdentityType()."\n";
echo '  Estimate setup time: '.($emergencyRequirement->getEstimateSetupTime() ?? 'N/A')."\n";

$country = $emergencyRequirement->country()->getIncluded();
if ($country) {
    echo '  Country: '.$country->getName()."\n";
}

$didGroupType = $emergencyRequirement->didGroupType()->getIncluded();
if ($didGroupType) {
    echo '  DID Group Type: '.$didGroupType->getName()."\n";
}

// Step 3: Find an identity
echo "\n=== Step 3: Find Identity ===\n";
$identities = Identity::all([
    'page' => ['size' => 10, 'number' => 1],
])->getData();
echo 'Found '.count($identities)." identities\n";

if (0 === count($identities)) {
    exit("No identities available. Please create an identity first.\n");
}

$identity = $identities[0];
echo 'Using identity: '.$identity->getId()."\n";
echo '  Name: '.$identity->getFirstName().' '.$identity->getLastName()."\n";
echo '  Phone: '.$identity->getPhoneNumber()."\n";

// Step 4: Find an address
echo "\n=== Step 4: Find Address ===\n";
$addresses = Address::all([
    'include' => 'country',
    'page' => ['size' => 10, 'number' => 1],
])->getData();
echo 'Found '.count($addresses)." addresses\n";

if (0 === count($addresses)) {
    exit("No addresses available. Please create an address first.\n");
}

$address = $addresses[0];
echo 'Using address: '.$address->getId()."\n";
echo '  Address: '.$address->getAddress()."\n";
echo '  City: '.$address->getCityName()."\n";
echo '  Postal Code: '.$address->getPostalCode()."\n";

$addressCountry = $address->country()->getIncluded();
if ($addressCountry) {
    echo '  Country: '.$addressCountry->getName()."\n";
}

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
    exit("Cannot proceed without valid requirement. Fix issues and try again.\n");
}
echo "Validation successful (HTTP 204)\n";

// Step 6: Create emergency verification
echo "\n=== Step 6: Create Emergency Verification ===\n";
$suffix = bin2hex(random_bytes(4));
$verification = new EmergencyVerification();
$verification->setAddress(Address::build($address->getId()));
$verification->setCallbackUrl('https://example.com/callbacks/emergency');
$verification->setCallbackMethod('POST');

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
echo '  Status: '.($verification->getStatus() instanceof BackedEnum ? $verification->getStatus()->value : $verification->getStatus())."\n";
echo '  Callback URL: '.($verification->getCallbackUrl() ?? 'N/A')."\n";

// Step 7: Check verification status
echo "\n=== Step 7: Check Verification Status ===\n";
$freshDocument = EmergencyVerification::find($verification->getId(), [
    'include' => 'address,emergency_calling_service',
]);
$freshVerification = $freshDocument->getData();
$status = $freshVerification->getStatus();
$statusString = $status instanceof BackedEnum ? $status->value : $status;
echo 'Verification '.$freshVerification->getId()." status: $statusString\n";

if ($freshVerification->isPending()) {
    echo "  Verification is pending review.\n";
} elseif ($freshVerification->isApproved()) {
    echo "  Verification has been approved.\n";
} elseif ($freshVerification->isRejected()) {
    echo "  Verification was rejected.\n";
    if ($freshVerification->getRejectReasons()) {
        echo '  Reject reasons: '.implode(', ', $freshVerification->getRejectReasons())."\n";
    }
    if ($freshVerification->getRejectComment()) {
        echo '  Reject comment: '.$freshVerification->getRejectComment()."\n";
    }
}

// Step 8: Get Emergency Calling Service (if available)
echo "\n=== Step 8: Get Emergency Calling Service ===\n";
$ecs = $freshVerification->emergencyCallingService()->getIncluded();
if ($ecs) {
    echo 'Emergency Calling Service: '.$ecs->getId()."\n";
    echo '  Name: '.$ecs->getName()."\n";
    echo '  Reference: '.($ecs->getReference() ?? 'N/A')."\n";
    echo '  Status: '.$ecs->getStatus()->value."\n";
    echo '  Created At: '.$ecs->getCreatedAt()->format('Y-m-d H:i:s')."\n";
    echo '  Renew Date: '.($ecs->getRenewDate() ?? 'N/A')."\n";
} else {
    echo "No Emergency Calling Service linked yet (verification may still be pending).\n";
    echo "Listing all Emergency Calling Services instead:\n";

    $allServices = EmergencyCallingService::all([
        'include' => 'country,did_group_type',
        'page' => ['size' => 5, 'number' => 1],
    ])->getData();

    if (0 === count($allServices)) {
        echo "  No emergency calling services found on this account.\n";
    } else {
        foreach ($allServices as $service) {
            echo '  ID: '.$service->getId()."\n";
            echo '    Name: '.$service->getName()."\n";
            echo '    Status: '.$service->getStatus()->value."\n";

            $serviceCountry = $service->country()->getIncluded();
            if ($serviceCountry) {
                echo '    Country: '.$serviceCountry->getName()."\n";
            }
            echo "\n";
        }
    }
}

echo "\n=== Emergency Scenario Complete ===\n";
