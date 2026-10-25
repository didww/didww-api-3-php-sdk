<?php

// Creates SMS exports (sms_in / sms_out) for yesterday and reads the columns
// of a completed SMS-Out export.
//
// Filters of SMS exports (Didww\Item\Export setters):
//   - setFilterFrom() / setFilterTo() (required): "YYYY-MM-DD HH:MM:SS" UTC;
//     from inclusive, to exclusive
//   - setFilterSourceAddressEq() / setFilterSourceAddressCont(),
//     setFilterDestinationAddressEq() / setFilterDestinationAddressCont(),
//     setFilterStatus(), setFilterSmsId()
//   - setFilterSmsTrunkId(): one id or an array of ids
//   - sms_in only: setFilterTrunkType(), one or more Didww\Enum\ExportTrunkType
//   - sms_out only: setFilterServiceType() ("a2p" or "p2p"),
//     setFilterSenderIdVerificationId()
//
// Usage: DIDWW_API_KEY=your_api_key php examples/exports_sms.php

require_once 'bootstrap.php';

use Didww\Enum\ExportStatus;
use Didww\Enum\ExportTrunkType;
use Didww\Enum\ExportType;
use Didww\Item\Export;

function createExport(Export $export, string $title): void
{
    echo "=== Creating $title ===\n";
    $document = $export->save();
    if ($document->hasErrors()) {
        echo "Error creating $title:\n";
        foreach ($document->getErrors() as $error) {
            echo '  - '.($error->getDetail() ?? $error->getTitle())."\n";
        }
    } else {
        $created = $document->getData();
        echo "Created $title: ".$created->getId().' (status: '.$created->getStatus()->value.")\n";
    }
    echo "\n";
}

$today = new DateTimeImmutable('today', new DateTimeZone('UTC'));
$from = $today->modify('-1 day')->format('Y-m-d H:i:s'); // inclusive
$to = $today->format('Y-m-d H:i:s');                      // exclusive
$suffix = bin2hex(random_bytes(4));

$smsOut = new Export();
$smsOut->setExportType(ExportType::SMS_OUT);
$smsOut->setFilterFrom($from);
$smsOut->setFilterTo($to);
$smsOut->setFilterServiceType('a2p');
$smsOut->setExternalReferenceId("php-sms-out-$suffix");
createExport($smsOut, 'SMS-Out export (A2P, yesterday)');

$smsIn = new Export();
$smsIn->setExportType(ExportType::SMS_IN);
$smsIn->setFilterFrom($from);
$smsIn->setFilterTo($to);
$smsIn->setFilterTrunkType([ExportTrunkType::HTTP_IN, ExportTrunkType::EMAIL]);
$smsIn->setExternalReferenceId("php-sms-in-$suffix");
createExport($smsIn, 'SMS-In export (HTTP IN and email trunks, yesterday)');

echo "=== Completed SMS-Out exports ===\n";
$completed = Export::all(['filter' => [
    'export_type' => ExportType::SMS_OUT->value,
    'status' => ExportStatus::COMPLETED->value,
]])->getData();
echo 'Found '.count($completed)." completed SMS-Out exports\n";
if (0 === count($completed) || !$completed[0]->url) {
    exit;
}

// The export file is a gzipped CSV; downloadAndDecompress() writes the CSV.
$export = $completed[0];
$csv = fopen('php://temp', 'w+b');
$result = $export->downloadAndDecompress($csv);
if (true !== $result) {
    exit($result."\n");
}
rewind($csv);
echo 'Export '.$export->getId().' ('.$export->getExportType()->value.') columns: '.trim((string) fgets($csv))."\n";
fclose($csv);
