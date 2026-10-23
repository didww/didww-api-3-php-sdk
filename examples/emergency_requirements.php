<?php

// List emergency requirements (2026-04-16).
// Each requirement may carry resource-level meta with setup_price and monthly_price
// when the customer has a matching plan.

require_once 'bootstrap.php';

use Didww\Item\EmergencyRequirement;

echo "=== Listing Emergency Requirements ===\n";
$document = EmergencyRequirement::all(['include' => 'country']);
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

    $numberType = $requirement->getNumberType();
    echo '  Number Type: '.($numberType instanceof BackedEnum ? $numberType->value : ($numberType ?? 'N/A'))."\n";

    $meta = $requirement->getMeta();
    if ($meta) {
        if (isset($meta['setup_price'])) {
            echo '  Setup price: '.$meta['setup_price']."\n";
        }
        if (isset($meta['monthly_price'])) {
            echo '  Monthly price: '.$meta['monthly_price']."\n";
        }
    }

    echo "\n";
}
