<?php

// Create emergency requirement validation (2026-04-16).

require_once 'bootstrap.php';

use Didww\Item\EmergencyRequirementValidation;
use Didww\Item\EmergencyRequirement;
use Didww\Item\Address;
use Didww\Item\Identity;

echo "=== Creating Emergency Requirement Validation ===\n";

// Build references to existing resources
$emergencyRequirement = EmergencyRequirement::build('your-emergency-requirement-id');
$address = Address::build('your-address-id');
$identity = Identity::build('your-identity-id');

$validation = new EmergencyRequirementValidation();
$validation->setEmergencyRequirement($emergencyRequirement);
$validation->setAddress($address);
$validation->setIdentity($identity);

$document = $validation->save();

if ($document->hasErrors()) {
    echo "Validation failed:\n";
    var_dump($document->getErrors());
} else {
    echo "Validation successful (HTTP 204)\n";
}
