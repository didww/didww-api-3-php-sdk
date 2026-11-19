<?php

// Assigns a DID to a voice in trunk, then to a voice in trunk group, and
// restores the DID's original routing and description at the end, also when
// a step fails. The trunk and the group are created by this script and
// deleted at the end.
//
// The trunk and the trunk group of a DID are exclusive: setVoiceInTrunk()
// sends voice_in_trunk_group as null in the same PATCH and
// setVoiceInTrunkGroup() sends voice_in_trunk as null. A DID built with
// Did::build() sends only what is set on it, so a description-only update
// keeps the routing.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/did_trunk_assignment.php

require_once 'bootstrap.php';

use Didww\Enum\Codec;
use Didww\Enum\TransportProtocol;
use Didww\Item\Configuration\Sip;
use Didww\Item\Did;
use Didww\Item\VoiceInTrunk;
use Didww\Item\VoiceInTrunkGroup;

function printErrors(string $message, $document): void
{
    echo $message.":\n";
    foreach ($document->getErrors() as $error) {
        echo '  - '.($error->getDetail() ?? $error->getTitle())."\n";
    }
}

function printVoiceRouting(Did $did): void
{
    $trunk = $did->voiceInTrunk()->getIncluded();
    $group = $did->voiceInTrunkGroup()->getIncluded();
    echo '   voice_in_trunk       = '.($trunk ? $trunk->getName() : 'null')."\n";
    echo '   voice_in_trunk_group = '.($group ? $group->name : 'null')."\n";
}

// Applies $change to the DID, saves it and prints its routing.
function updateDid(string $didId, callable $change, string $what): void
{
    $did = Did::build($didId);
    $change($did);
    $document = $did->save(['include' => 'voice_in_trunk,voice_in_trunk_group']);
    if ($document->hasErrors()) {
        printErrors("Error $what", $document);

        return;
    }
    printVoiceRouting($document->getData());
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

echo "=== Finding DID ===\n";
$dids = Did::all([
    'include' => 'voice_in_trunk,voice_in_trunk_group',
    'page' => ['size' => 1, 'number' => 1],
])->getData();
if (0 === count($dids)) {
    exit("No DIDs found. Please order a DID first.\n");
}
$did = $dids[0];
// Remember the routing and the description to put them back at the end
$originalTrunk = $did->voiceInTrunk()->getIncluded();
$originalGroup = $did->voiceInTrunkGroup()->getIncluded();
$originalDescription = $did->getDescription();
echo 'Using DID: '.$did->getNumber().' ('.$did->getId().")\n";
printVoiceRouting($did);

$suffix = bin2hex(random_bytes(4));

$trunk = $group = null;
$changed = false;
try {
    echo "\n=== Creating a trunk and a trunk group ===\n";
    $newTrunk = new VoiceInTrunk();
    $newTrunk->setName("PHP Assignment Trunk $suffix");
    $newTrunk->setConfiguration(new Sip([
        'host' => 'sip.example.com',
        'port' => 5060,
        'codecs' => [Codec::PCMU, Codec::PCMA],
        'transport_protocol_id' => TransportProtocol::UDP,
    ]));
    $document = $newTrunk->save();
    if ($document->hasErrors()) {
        printErrors('Error creating the trunk', $document);
    } else {
        $trunk = $document->getData();
        echo 'Created trunk: '.$trunk->getName()."\n";
    }

    $newGroup = new VoiceInTrunkGroup();
    $newGroup->name = "PHP Assignment Group $suffix";
    $newGroup->capacity_limit = 10;
    $document = $newGroup->save();
    if ($document->hasErrors()) {
        printErrors('Error creating the trunk group', $document);
    } else {
        $group = $document->getData();
        echo 'Created trunk group: '.$group->name."\n";
    }

    if (null !== $trunk) {
        echo "\n=== 1. Assigning the trunk (sends voice_in_trunk_group as null) ===\n";
        $changed = true;
        updateDid($did->getId(), fn (Did $d) => $d->setVoiceInTrunk(VoiceInTrunk::build($trunk->getId())), 'assigning the trunk');
    }

    if (null !== $group) {
        echo "\n=== 2. Assigning the trunk group (sends voice_in_trunk as null) ===\n";
        $changed = true;
        updateDid($did->getId(), fn (Did $d) => $d->setVoiceInTrunkGroup(VoiceInTrunkGroup::build($group->getId())), 'assigning the trunk group');
    }

    if (null !== $trunk) {
        echo "\n=== 3. Assigning the trunk again (clears the trunk group) ===\n";
        updateDid($did->getId(), fn (Did $d) => $d->setVoiceInTrunk(VoiceInTrunk::build($trunk->getId())), 'assigning the trunk');

        echo "\n=== 4. Updating the description only (the trunk stays) ===\n";
        updateDid($did->getId(), fn (Did $d) => $d->setDescription('DID with a trunk assigned'), 'updating the description');
    }
} finally {
    if ($changed) {
        echo "\n=== 5. Restoring the original routing and description ===\n";
        updateDid($did->getId(), function (Did $d) use ($originalTrunk, $originalGroup, $originalDescription) {
            if ($originalTrunk) {
                $d->setVoiceInTrunk(VoiceInTrunk::build($originalTrunk->getId()));
            } elseif ($originalGroup) {
                $d->setVoiceInTrunkGroup(VoiceInTrunkGroup::build($originalGroup->getId()));
            } else {
                // Unroutes the DID: sends voice_in_trunk and voice_in_trunk_group as null.
                $d->voiceInTrunk()->dissociate();
                $d->voiceInTrunkGroup()->dissociate();
            }
            $d->setDescription($originalDescription);
        }, 'restoring the routing');
    }

    echo "\n=== Cleaning up ===\n";
    // The DID is routed back first, so the trunk and the group are no longer in use
    if (null !== $trunk) {
        deleteItem($trunk, 'trunk');
    }
    if (null !== $group) {
        deleteItem($group, 'trunk group');
    }
}
