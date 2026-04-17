<?php

// CRUD for voice in trunk groups with trunk relationships.
// 2026-04-16 adds external_reference_id for customer tagging.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/voice_in_trunk_groups.php

require_once 'bootstrap.php';

use Didww\Item\VoiceInTrunkGroup;

// List trunk groups
echo "=== Existing Trunk Groups ===\n";
$document = VoiceInTrunkGroup::all(['include' => 'voice_in_trunks']);
$trunkGroups = $document->getData();
echo 'Found '.count($trunkGroups)." trunk groups\n";

foreach (array_slice($trunkGroups, 0, 3) as $group) {
    $trunks = $group->voiceInTrunks()->getIncluded();
    $trunksCount = $trunks ? count($trunks->all()) : 0;
    echo $group->getName()." ($trunksCount trunks)\n";
}

// Create a new trunk group (2026-04-16 external_reference_id for customer tagging)
echo "\n=== Creating Trunk Group ===\n";
$suffix = bin2hex(random_bytes(4));

$trunkGroup = new VoiceInTrunkGroup();
$trunkGroup->setName("PHP Trunk Group $suffix");
$trunkGroup->setCapacityLimit(20);
$trunkGroup->setExternalReferenceId("php-tg-$suffix");

$document = $trunkGroup->save();

if ($document->hasErrors()) {
    echo "Error creating trunk group:\n";
    var_dump($document->getErrors());
} else {
    $trunkGroup = $document->getData();
    echo 'Created trunk group: '.$trunkGroup->getId().' - '.$trunkGroup->getName()."\n";
    echo '  External reference: '.($trunkGroup->getExternalReferenceId() ?? 'null')."\n";

    // Update trunk group
    echo "\n=== Updating Trunk Group ===\n";
    $trunkGroup->setCapacityLimit(30);
    $updateDoc = $trunkGroup->save();

    if ($updateDoc->hasErrors()) {
        echo "Error updating trunk group:\n";
        var_dump($updateDoc->getErrors());
    } else {
        $trunkGroup = $updateDoc->getData();
        echo 'Updated trunk group: '.$trunkGroup->getName().' (capacity_limit: '.$trunkGroup->getCapacityLimit().")\n";

        // Delete trunk group
        echo "\n=== Deleting Trunk Group ===\n";
        $deleteDoc = $trunkGroup->delete();
        echo $deleteDoc->hasErrors() ? "Error deleting trunk group\n" : "Trunk group deleted\n";
    }
}
