<?php

// Voice in trunk groups: creates two SIP trunks, creates a trunk group with
// both, lists the groups with their trunks, renames the group and deletes
// it, also when a step fails. Deleting a group also deletes the trunks in it.
// 2026-04-16 adds external_reference_id for customer tagging.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/voice_in_trunk_groups.php

require_once 'bootstrap.php';

use Didww\Enum\Codec;
use Didww\Enum\TransportProtocol;
use Didww\Item\Configuration\Sip;
use Didww\Item\VoiceInTrunk;
use Didww\Item\VoiceInTrunkGroup;
use Swis\JsonApi\Client\Collection;

function printErrors(string $message, $document): void
{
    echo $message.":\n";
    foreach ($document->getErrors() as $error) {
        echo '  - '.($error->getDetail() ?? $error->getTitle())."\n";
    }
}

function deleteItem($item, string $what): void
{
    $document = $item->delete();
    if ($document->hasErrors()) {
        printErrors("Error deleting $what ".$item->getId(), $document);
    } else {
        echo ucfirst($what).' '.$item->getId()." deleted\n";
    }
}

$suffix = bin2hex(random_bytes(4));

$trunks = [];
$group = null;
try {
    echo "=== Creating Trunks ===\n";
    foreach (['A' => 'sip-a.example.com', 'B' => 'sip-b.example.com'] as $label => $host) {
        $trunk = new VoiceInTrunk();
        $trunk->setName("PHP Group Trunk $label $suffix");
        $trunk->setConfiguration(new Sip([
            'host' => $host,
            'port' => 5060,
            'codecs' => [Codec::PCMU, Codec::PCMA],
            'transport_protocol_id' => TransportProtocol::UDP,
        ]));
        $document = $trunk->save();
        if ($document->hasErrors()) {
            printErrors("Error creating trunk $label", $document);
            continue;
        }
        $trunks[] = $document->getData();
        echo "Created trunk $label: ".$document->getData()->getId()."\n";
    }

    if ([] !== $trunks) {
        echo "\n=== Creating Trunk Group ===\n";
        $newGroup = new VoiceInTrunkGroup();
        $newGroup->name = "PHP Trunk Group $suffix";
        $newGroup->capacity_limit = 10;
        $newGroup->setExternalReferenceId("php-tg-$suffix");
        $newGroup->setVoiceInTrunks(new Collection(array_map(fn ($trunk) => VoiceInTrunk::build($trunk->getId()), $trunks)));
        $document = $newGroup->save(['include' => 'voice_in_trunks']);
        if ($document->hasErrors()) {
            printErrors('Error creating trunk group', $document);
        } else {
            $group = $document->getData();
            echo 'Created trunk group: '.$group->getId().' - '.$group->name.' with '.count($group->voiceInTrunks()->getIncluded())." trunks\n";
            echo '  External reference: '.($group->getExternalReferenceId() ?? 'null')."\n";
        }
    }

    echo "\n=== Trunk Groups ===\n";
    $groups = VoiceInTrunkGroup::all(['include' => 'voice_in_trunks'])->getData();
    echo 'Found '.count($groups)." trunk groups\n";
    foreach ($groups as $listed) {
        echo '  '.$listed->name.' ('.count($listed->voiceInTrunks()->getIncluded()).' trunks), external reference: '.($listed->getExternalReferenceId() ?? 'null')."\n";
    }

    if (null !== $group) {
        echo "\n=== Renaming Trunk Group ===\n";
        // A group built with its ID sends only the name; the trunks stay.
        $rename = VoiceInTrunkGroup::build($group->getId());
        $rename->name = "PHP Trunk Group $suffix (renamed)";
        $document = $rename->save();
        if ($document->hasErrors()) {
            printErrors('Error renaming trunk group', $document);
        } else {
            echo 'Renamed trunk group: '.$document->getData()->name."\n";
        }
    }
} finally {
    echo "\n=== Cleaning Up ===\n";
    if (null !== $group) {
        // Deleting the group also deletes its trunks
        deleteItem($group, 'trunk group');
    } else {
        foreach ($trunks as $trunk) {
            deleteItem($trunk, 'trunk');
        }
    }
}
