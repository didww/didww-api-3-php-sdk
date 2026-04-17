<?php

// List emergency requirements (2026-04-16).

require_once 'bootstrap.php';

use Didww\Item\EmergencyRequirement;

echo "=== Listing Emergency Requirements ===\n";
$document = EmergencyRequirement::all(['include' => 'country,did_group_type']);
$requirements = $document->getData();
echo 'Found '.count($requirements)." emergency requirements\n";

foreach ($requirements as $requirement) {
    echo 'ID: '.$requirement->getId()."\n";
    echo '  Identity Type: '.$requirement->getIdentityType()."\n";
    echo '  Estimate Setup Time: '.($requirement->getEstimateSetupTime() ?? 'N/A')."\n";
    echo '  Restriction: '.($requirement->getRequirementRestrictionMessage() ?? 'none')."\n";

    $country = $requirement->country()->getIncluded();
    if ($country) {
        echo '  Country: '.$country->getName()."\n";
    }

    $didGroupType = $requirement->didGroupType()->getIncluded();
    if ($didGroupType) {
        echo '  DID Group Type: '.$didGroupType->getName()."\n";
    }

    echo "\n";
}
