<?php

// List, create, and download exports.
// Demonstrates external_reference_id and from/to filter semantics.

require_once 'bootstrap.php';

use Didww\Enum\ExportType;
use Didww\Item\Export;

// List exports
echo "=== Listing Exports ===\n";
$document = Export::all(['page' => ['size' => 5, 'number' => 1]]);
$exports = $document->getData();
echo 'Found '.count($exports)." exports\n";

foreach ($exports as $export) {
    echo 'ID: '.$export->getId()."\n";
    echo '  Type: '.$export->getExportType()->value."\n";
    echo '  External Reference ID: '.($export->getExternalReferenceId() ?? 'null')."\n";
    echo "\n";
}

// Create a CDR export with from/to filters
// Note: 'from' is inclusive, 'to' is exclusive
echo "\n=== Creating CDR Export ===\n";
$suffix = bin2hex(random_bytes(4));

$export = new Export();
$export->setExportType(ExportType::CDR_OUT);
$export->setFilterFrom(gmdate('Y-m-d', strtotime('-1 day')).' 00:00:00');
$export->setFilterTo(gmdate('Y-m-d').' 00:00:00');
$export->setExternalReferenceId("php-export-$suffix");

$document = $export->save();

if ($document->hasErrors()) {
    echo "Error creating export:\n";
    var_dump($document->getErrors());
} else {
    $export = $document->getData();
    echo 'Created export: '.$export->getId()."\n";
    echo '  External Reference ID: '.($export->getExternalReferenceId() ?? 'null')."\n";
}
