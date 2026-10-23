<?php

// CRUD for SMS trunks: lists trunks, creates an HTTP IN trunk, updates its
// configuration, regenerates the credentials of an HTTP OUT trunk, and deletes
// both trunks. Only the trunks this script creates are changed or deleted.
//
// Configuration types (Didww\Item\SmsTrunkConfiguration):
//   - SmsToEmail, HttpIn: DIDWW delivers inbound messages by email or HTTP
//   - HttpOut, SmppEsme: you connect to DIDWW; DIDWW generates the
//     credentials, which are read-only and never sent back
//   - SmppSmsc: DIDWW binds to your SMSC
//
// The read-only getAllowIn() / getAllowOut() tell which directions a trunk
// handles.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/sms_trunks.php

require_once 'bootstrap.php';

use Didww\Enum\SmsBodyType;
use Didww\Enum\SmsHttpMethod;
use Didww\Item\SmsTrunk;
use Didww\Item\SmsTrunkConfiguration\HttpIn;
use Didww\Item\SmsTrunkConfiguration\HttpOut;

function printSmsTrunk(SmsTrunk $trunk): void
{
    $configuration = $trunk->getConfiguration();
    echo $trunk->getName().' ['.($configuration ? $configuration->getType() : 'none')."]\n";
    echo '  ID: '.$trunk->getId()."\n";
    echo '  Inbound: '.var_export($trunk->getAllowIn(), true).', outbound: '.var_export($trunk->getAllowOut(), true)."\n";
    $group = $trunk->smsTrunkGroup()->getIncluded();
    if ($group) {
        echo '  Group: '.$group->getName()."\n";
    }
    if (null !== $trunk->getHost()) {
        echo '  Submit URL: '.$trunk->getHost()."\n";
    }
}

function printErrors(string $message, $document): void
{
    echo $message.":\n";
    foreach ($document->getErrors() as $error) {
        echo '  - '.($error->getDetail() ?? $error->getTitle())."\n";
    }
}

function deleteSmsTrunk(SmsTrunk $trunk): void
{
    $document = $trunk->delete();
    if ($document->hasErrors()) {
        printErrors('Error deleting SMS trunk '.$trunk->getId(), $document);
    } else {
        echo 'SMS trunk '.$trunk->getName()." deleted\n";
    }
}

echo "=== Existing SMS Trunks ===\n";
$trunks = SmsTrunk::all([
    'include' => 'sms_trunk_group',
    'page' => ['size' => 10, 'number' => 1],
])->getData();
echo 'Found '.count($trunks)." SMS trunks\n";
foreach ($trunks as $trunk) {
    printSmsTrunk($trunk);
}

$suffix = bin2hex(random_bytes(4));

echo "\n=== Creating HTTP IN Trunk ===\n";
$configuration = new HttpIn();
$configuration->setUrl('https://example.com/sms');
$configuration->setHttpMethod(SmsHttpMethod::POST);
$configuration->setBodyType(SmsBodyType::JSON);
// Placeholders: {SMS_TEXT}, {SMS_TEXT_BASE64_ENCODED}, {SMS_SRC_ADDR}, {SMS_DST_ADDR}, {SMS_TIME}
$configuration->setBody('{"text":"{SMS_TEXT}","from":"{SMS_SRC_ADDR}","to":"{SMS_DST_ADDR}"}');
$configuration->setValidateHttpsCertificate(true);

$trunk = new SmsTrunk();
$trunk->setName("PHP HTTP IN $suffix");
$trunk->setExternalReferenceId("php-sms-$suffix");
$trunk->setConfiguration($configuration);

$document = $trunk->save();
if ($document->hasErrors()) {
    printErrors('Error creating SMS trunk', $document);
    exit(1);
}
$httpIn = $document->getData();
echo 'Created SMS trunk: '.$httpIn->getId().' - '.$httpIn->getName()."\n";
echo '  Configuration: '.$httpIn->getConfiguration()->getType().' -> '.$httpIn->getConfiguration()->getUrl()."\n";

try {
    // The configuration type cannot change; attributes left out keep their values.
    echo "\n=== Updating HTTP IN Trunk ===\n";
    $configuration = new HttpIn();
    $configuration->setUrl('https://example.com/sms/v2');
    $httpIn->setName("PHP HTTP IN $suffix (v2)");
    $httpIn->setConfiguration($configuration);

    $document = $httpIn->save();
    if ($document->hasErrors()) {
        printErrors('Error updating SMS trunk', $document);
    } else {
        $updated = $document->getData();
        echo 'Updated: '.$updated->getName().' -> '.$updated->getConfiguration()->getUrl()."\n";
        $httpMethod = $updated->getConfiguration()->getHttpMethod();
        echo '  HTTP method kept: '.($httpMethod instanceof BackedEnum ? $httpMethod->value : $httpMethod)."\n";
    }

    // The generated credentials of http_out and smpp_esme trunks are rotated
    // with an SmsTrunkRegenerateCredential and read back from the trunk.
    echo "\n=== Creating HTTP OUT Trunk and Regenerating Its Credentials ===\n";
    $configuration = new HttpOut();
    $configuration->setCallbackUrl('https://example.com/sms/status');
    $configuration->setAllowedIps(['203.0.113.10']);

    $trunk = new SmsTrunk();
    $trunk->setName("PHP HTTP OUT $suffix");
    $trunk->setConfiguration($configuration);

    $document = $trunk->save();
    if ($document->hasErrors()) {
        printErrors('Error creating HTTP OUT trunk', $document);
    } else {
        $httpOut = $document->getData();
        echo 'Created SMS trunk: '.$httpOut->getId().' - '.$httpOut->getName()."\n";
        try {
            $document = $httpOut->regenerateCredentials();
            if ($document->hasErrors()) {
                printErrors('Error regenerating credentials', $document);
            } else {
                $reloaded = SmsTrunk::find($httpOut->getId())->getData();
                // Use getAuthName() and getAuthPassword() to submit messages to
                // getHost(); debug output of the configuration masks them.
                echo "Credentials regenerated:\n";
                print_r($reloaded->getConfiguration());
                echo '  Submit URL: '.($reloaded->getHost() ?? 'N/A')."\n";
            }
        } finally {
            deleteSmsTrunk($httpOut);
        }
    }
} finally {
    echo "\n=== Deleting HTTP IN Trunk ===\n";
    deleteSmsTrunk($httpIn);
}
