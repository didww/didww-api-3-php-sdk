<?php

// List emergency calling services (2026-04-16).
// Each service may carry resource-level meta with setup_price and monthly_price.

require_once 'bootstrap.php';

use Didww\Item\EmergencyCallingService;

echo "=== Listing Emergency Calling Services ===\n";
$document = EmergencyCallingService::all([
    'include' => 'country,did_group_type,emergency_requirement',
    'page' => ['size' => 10, 'number' => 1],
]);
$services = $document->getData();
echo 'Found '.count($services)." emergency calling services\n";

foreach ($services as $service) {
    echo 'ID: '.$service->getId()."\n";
    echo '  Name: '.$service->getName()."\n";
    echo '  Reference: '.$service->getReference()."\n";
    echo '  Status: '.$service->getStatus()->value."\n";
    echo '  Created At: '.$service->getCreatedAt()->format('Y-m-d H:i:s')."\n";
    $renewDate = $service->getRenewDate();
    echo '  Renew Date: '.($renewDate ? $renewDate->format('Y-m-d') : 'N/A')."\n";

    $country = $service->country()->getIncluded();
    if ($country) {
        echo '  Country: '.$country->getName()."\n";
    }

    $meta = $service->getMeta();
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
