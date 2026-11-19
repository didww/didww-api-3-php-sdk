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

// Stops the flow; the DID is restored and the trunk and group are deleted.
final class FlowStopped extends RuntimeException
{
}

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

// Applies $change to the DID, saves it and prints its routing; returns
// whether the API accepted the change.
function updateDid(string $didId, callable $change, string $what): bool
{
    $did = Did::build($didId);
    $change($did);
    $document = $did->save(['include' => 'voice_in_trunk,voice_in_trunk_group']);
    if ($document->hasErrors()) {
        printErrors("Error $what", $document);

        return false;
    }
    printVoiceRouting($document->getData());

    return true;
}

// Reports a failed delete, also a thrown one, so that the clean-up goes on.
function deleteItem($item, string $what): void
{
    try {
        $document = $item->delete();
    } catch (Throwable $e) {
        echo "Error deleting $what ".$item->getId().': '.$e->getMessage()."\n";

        return;
    }
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
if ($dids->isEmpty()) {
    echo "No DIDs found. Please order a DID first.\n";
    exit(1);
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
$exitCode = 0;
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
        throw new FlowStopped('Stopped: the trunk was not created');
    }
    $trunk = $document->getData();
    echo 'Created trunk: '.$trunk->getName()."\n";

    $newGroup = new VoiceInTrunkGroup();
    $newGroup->name = "PHP Assignment Group $suffix";
    $newGroup->capacity_limit = 10;
    $document = $newGroup->save();
    if ($document->hasErrors()) {
        printErrors('Error creating the trunk group', $document);
        throw new FlowStopped('Stopped: the trunk group was not created');
    }
    $group = $document->getData();
    echo 'Created trunk group: '.$group->name."\n";

    $steps = [
        '1. Assigning the trunk (sends voice_in_trunk_group as null)' => fn (Did $d) => $d->setVoiceInTrunk(VoiceInTrunk::build($trunk->getId())),
        '2. Assigning the trunk group (sends voice_in_trunk as null)' => fn (Did $d) => $d->setVoiceInTrunkGroup(VoiceInTrunkGroup::build($group->getId())),
        '3. Assigning the trunk again (clears the trunk group)' => fn (Did $d) => $d->setVoiceInTrunk(VoiceInTrunk::build($trunk->getId())),
        '4. Updating the description only (the trunk stays)' => fn (Did $d) => $d->setDescription('DID with a trunk assigned'),
    ];
    foreach ($steps as $title => $change) {
        echo "\n=== $title ===\n";
        $changed = true;
        if (!updateDid($did->getId(), $change, lcfirst(substr($title, 3)))) {
            throw new FlowStopped("Stopped: step $title failed");
        }
    }
} catch (FlowStopped $e) {
    echo "\n".$e->getMessage()."\n";
    $exitCode = 1;
} finally {
    $restored = true;
    if ($changed) {
        echo "\n=== 5. Restoring the original routing and description ===\n";
        $restored = updateDid($did->getId(), function (Did $d) use ($originalTrunk, $originalGroup, $originalDescription) {
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
    if (!$restored) {
        // The DID may still use them: they are kept so that its calls keep a route
        foreach (['trunk' => $trunk, 'trunk group' => $group] as $what => $item) {
            if (null !== $item) {
                echo 'Kept '.$what.' '.$item->getId()." because the DID routing was not restored\n";
            }
        }
        exit(1);
    }
    // The DID is routed back first, so the trunk and the group are no longer in use
    if (null !== $trunk) {
        deleteItem($trunk, 'trunk');
    }
    if (null !== $group) {
        deleteItem($group, 'trunk group');
    }
}

exit($exitCode);
