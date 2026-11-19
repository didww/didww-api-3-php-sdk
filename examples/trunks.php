<?php

// Voice IN trunks: lists the trunks with their trunk group included, finds
// one by ID, creates a SIP trunk and a PSTN trunk, updates both and deletes
// both, also when a step fails. Updates send only the changed attributes.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/trunks.php

require_once 'bootstrap.php';

use Didww\Enum\CliFormat;
use Didww\Enum\Codec;
use Didww\Enum\IceMode;
use Didww\Enum\MediaEncryptionMode;
use Didww\Enum\Pop;
use Didww\Enum\SstRefreshMethod;
use Didww\Enum\TransportProtocol;
use Didww\Item\Configuration\Base;
use Didww\Item\Configuration\Pstn;
use Didww\Item\Configuration\Sip;
use Didww\Item\VoiceInTrunk;

function printErrors(string $message, $document): void
{
    echo $message.":\n";
    foreach ($document->getErrors() as $error) {
        echo '  - '.($error->getDetail() ?? $error->getTitle())."\n";
    }
}

function enumValue($value): string
{
    if ($value instanceof BackedEnum) {
        return (string) $value->value;
    }

    return null === $value ? 'null' : (string) $value;
}

function printTrunk(VoiceInTrunk $trunk): void
{
    $configuration = $trunk->getConfiguration();
    echo $trunk->getName().' ['.($configuration ? $configuration->getType() : 'none')."]\n";
    echo '  ID: '.$trunk->getId()."\n";
    // null: DIDWW chooses the point of presence
    echo '  POP: '.enumValue($trunk->getPop())."\n";
    if ($configuration instanceof Sip) {
        echo '  Host: '.enumValue($configuration->getHost()).':'.enumValue($configuration->getPort())."\n";
        echo '  Codecs: '.implode(', ', array_map('enumValue', $configuration->getCodecs() ?? []))."\n";
        echo '  ICE mode: '.enumValue($configuration->getIceMode())."\n";
    } elseif ($configuration instanceof Pstn) {
        echo '  Destination: '.$configuration->getDst()."\n";
    }
}

// Reports a failed delete, also a thrown one, so that the clean-up goes on.
function deleteTrunk(VoiceInTrunk $trunk): void
{
    try {
        $document = $trunk->delete();
    } catch (Throwable $e) {
        echo 'Error deleting trunk '.$trunk->getId().': '.$e->getMessage()."\n";

        return;
    }
    if ($document->hasErrors()) {
        printErrors('Error deleting trunk '.$trunk->getId(), $document);
    } else {
        echo 'Trunk '.$trunk->getName()." deleted\n";
    }
}

echo "=== Voice IN Trunks ===\n";
$trunks = VoiceInTrunk::all([
    'include' => 'voice_in_trunk_group',
    'page' => ['size' => 10, 'number' => 1],
])->getData();
echo 'Found '.count($trunks)." trunks\n";
foreach ($trunks as $trunk) {
    $group = $trunk->voiceInTrunkGroup()->getIncluded();
    $configuration = $trunk->getConfiguration();
    echo '  '.$trunk->getName().' ['.($configuration ? $configuration->getType() : 'none').']';
    echo ' group: '.($group ? $group->name : 'none')."\n";
}

if ($trunks->isNotEmpty()) {
    echo "\n=== Find a Trunk ===\n";
    printTrunk(VoiceInTrunk::find($trunks[0]->getId())->getData());
}

// set name (should be unique)
$suffix = bin2hex(random_bytes(4));

$sipTrunk = $pstnTrunk = null;
try {
    echo "\n=== Creating SIP Trunk ===\n";
    $sip = new Sip([
        'username' => 'username',
        'host' => '203.0.113.110',
        'sst_refresh_method_id' => SstRefreshMethod::INVITE,
        'transport_protocol_id' => TransportProtocol::UDP,
        'media_encryption_mode' => MediaEncryptionMode::DISABLED,
        'port' => 5060,
        // codec names in priority order; left out, the trunk offers pcmu, pcma, g729, g723 and telephone_event
        'codecs' => [Codec::PCMA, Codec::PCMU, Codec::TELEPHONE_EVENT],
        'ice_mode' => IceMode::ACCEPT,
        'rerouting_disconnect_code_ids' => Base::getDefaultReroutingDisconnectCodeIds(),
    ]);
    $trunk = new VoiceInTrunk();
    $trunk->setName("PHP SIP Trunk $suffix");
    $trunk->setCliFormat(CliFormat::RAW);
    $trunk->setRingingTimeout(30);
    // optional; leave it unset to let DIDWW choose
    $trunk->setPop(Pop::FRA);
    $trunk->setConfiguration($sip);
    $document = $trunk->save();
    if ($document->hasErrors()) {
        printErrors('Error creating SIP trunk', $document);
    } else {
        $sipTrunk = $document->getData();
        printTrunk($sipTrunk);
        echo '  CLI format: '.enumValue($sipTrunk->getCliFormat()).', ringing timeout: '.$sipTrunk->getRingingTimeout()."\n";
    }

    echo "\n=== Creating PSTN Trunk ===\n";
    $trunk = new VoiceInTrunk();
    $trunk->setName("PHP PSTN Trunk $suffix");
    $trunk->setRingingTimeout(30);
    $trunk->setConfiguration(new Pstn(['dst' => '12125551234']));
    $document = $trunk->save();
    if ($document->hasErrors()) {
        printErrors('Error creating PSTN trunk', $document);
    } else {
        $pstnTrunk = $document->getData();
        printTrunk($pstnTrunk);
    }

    if (null !== $sipTrunk) {
        echo "\n=== Updating SIP Trunk ===\n";
        $sipTrunk->setDescription('Updated by the PHP example');
        $sipTrunk->setCliFormat(CliFormat::E164);
        $sipTrunk->setPop(null);
        $document = $sipTrunk->save();
        if ($document->hasErrors()) {
            printErrors('Error updating SIP trunk', $document);
        } else {
            $updated = $document->getData();
            printTrunk($updated);
            echo '  CLI format: '.enumValue($updated->getCliFormat()).', description: '.$updated->getDescription()."\n";
        }
    }

    if (null !== $pstnTrunk) {
        echo "\n=== Updating PSTN Trunk ===\n";
        $pstnTrunk->setName("PHP PSTN Trunk $suffix (updated)");
        // The configuration is sent as a whole; its type cannot change
        $pstnTrunk->setConfiguration(new Pstn(['dst' => '12125550100']));
        $document = $pstnTrunk->save();
        if ($document->hasErrors()) {
            printErrors('Error updating PSTN trunk', $document);
        } else {
            printTrunk($document->getData());
        }
    }
} finally {
    echo "\n=== Deleting Trunks ===\n";
    foreach ([$sipTrunk, $pstnTrunk] as $trunk) {
        if (null !== $trunk) {
            deleteTrunk($trunk);
        }
    }
}
