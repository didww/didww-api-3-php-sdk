<?php

// List and manage emergency calling services (2026-04-16).

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

    echo "\n";
}
