<?php

// List DID history entries (2026-04-16).

require_once 'bootstrap.php';

use Didww\Item\DidHistory;

echo "=== Listing DID History ===\n";
$document = DidHistory::all(['page' => ['size' => 10, 'number' => 1]]);
$entries = $document->getData();
echo "Found " . count($entries) . " history entries\n";

foreach ($entries as $entry) {
    echo "DID: " . $entry->getDidNumber() . "\n";
    echo "  Action: " . $entry->getAction() . "\n";
    echo "  Method: " . $entry->getMethod() . "\n";
    echo "  Created At: " . $entry->getCreatedAt()->format('Y-m-d H:i:s') . "\n";
    echo "\n";
}
