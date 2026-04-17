<?php

// List address verifications and update external_reference_id (2026-04-16).

require_once 'bootstrap.php';

use Didww\Item\AddressVerification;

echo "=== Listing Address Verifications ===\n";
$document = AddressVerification::all(['page' => ['size' => 5, 'number' => 1]]);
$verifications = $document->getData();
echo "Found " . count($verifications) . " address verifications\n";

foreach ($verifications as $verification) {
    echo "ID: " . $verification->getId() . "\n";
    echo "  Status: " . $verification->getStatus()->value . "\n";
    echo "  External Reference ID: " . ($verification->getExternalReferenceId() ?? 'null') . "\n";

    if ($verification->getRejectComment()) {
        echo "  Reject Comment: " . $verification->getRejectComment() . "\n";
    }

    echo "\n";
}
