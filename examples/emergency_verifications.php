<?php

// List and create emergency verifications (2026-04-16).

require_once 'bootstrap.php';

use Didww\Item\EmergencyVerification;

echo "=== Listing Emergency Verifications ===\n";
$document = EmergencyVerification::all([
    'include' => 'address,emergency_calling_service',
    'page' => ['size' => 10, 'number' => 1],
]);
$verifications = $document->getData();
echo "Found " . count($verifications) . " emergency verifications\n";

foreach ($verifications as $verification) {
    echo "ID: " . $verification->getId() . "\n";
    echo "  Reference: " . $verification->getReference() . "\n";
    echo "  Status: " . $verification->getStatus() . "\n";
    echo "  External Reference ID: " . ($verification->getExternalReferenceId() ?? 'null') . "\n";
    echo "  Created At: " . $verification->getCreatedAt()->format('Y-m-d H:i:s') . "\n";

    if ($verification->getRejectReasons()) {
        echo "  Reject Reasons: " . implode(', ', $verification->getRejectReasons()) . "\n";
    }
    if ($verification->getRejectComment()) {
        echo "  Reject Comment: " . $verification->getRejectComment() . "\n";
    }

    echo "\n";
}
