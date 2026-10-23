<?php

// CRUD for SMS trunk groups: creates two inbound SMS trunks, puts them in a
// group, lists groups, and deletes the group. Only the trunks and the group
// this script creates are changed or deleted.
//
// A group spreads a DID's inbound messages over its trunks by their priority
// (higher first). http_out trunks cannot join a group. Deleting a group also
// deletes the trunks in it.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/sms_trunk_groups.php

require_once 'bootstrap.php';

use Didww\Enum\SmsHttpMethod;
use Didww\Item\SmsTrunk;
use Didww\Item\SmsTrunkConfiguration\HttpIn;
use Didww\Item\SmsTrunkConfiguration\SmsToEmail;
use Didww\Item\SmsTrunkGroup;
use Swis\JsonApi\Client\Collection;

function printErrors(string $message, $document): void
{
    echo $message.":\n";
    foreach ($document->getErrors() as $error) {
        echo '  - '.($error->getDetail() ?? $error->getTitle())."\n";
    }
}

// Creates an SMS trunk, or returns null after printing the errors.
function createSmsTrunk(string $name, int $priority, $configuration): ?SmsTrunk
{
    $trunk = new SmsTrunk();
    $trunk->setName($name);
    $trunk->setPriority($priority);
    $trunk->setConfiguration($configuration);

    $document = $trunk->save();
    if ($document->hasErrors()) {
        printErrors('Error creating SMS trunk '.$name, $document);

        return null;
    }
    $trunk = $document->getData();
    echo 'Created SMS trunk: '.$trunk->getId().' - '.$trunk->getName().' (priority '.$trunk->getPriority().")\n";

    return $trunk;
}

function deleteItem($item, string $what): void
{
    $document = $item->delete();
    if ($document->hasErrors()) {
        printErrors('Error deleting '.$what.' '.$item->getId(), $document);
    } else {
        echo ucfirst($what).' '.$item->getName()." deleted\n";
    }
}

$suffix = bin2hex(random_bytes(4));

echo "=== Creating SMS Trunks ===\n";
$webhook = new HttpIn();
$webhook->setUrl('https://example.com/sms');
$webhook->setHttpMethod(SmsHttpMethod::GET);
$webhook->setQueryParameters(['text' => '{SMS_TEXT}', 'from' => '{SMS_SRC_ADDR}']);

$email = new SmsToEmail();
$email->setSendTo('sms@example.com');
$email->setSubject('Inbound SMS');
$email->setMessage('You have a new message.');

$trunks = array_filter([
    createSmsTrunk("PHP primary $suffix", 200, $webhook),
    createSmsTrunk("PHP fallback $suffix", 100, $email),
]);

// Groups the trunks; returns the group, or null after printing the errors.
function groupTrunks(array $trunks, string $suffix): ?SmsTrunkGroup
{
    echo "\n=== Creating SMS Trunk Group ===\n";
    $group = new SmsTrunkGroup();
    $group->setName("PHP SMS Group $suffix");
    $group->setExternalReferenceId("php-sms-group-$suffix");
    $group->setSmsTrunks(new Collection(array_map(static fn (SmsTrunk $t) => SmsTrunk::build($t->getId()), $trunks)));

    $document = $group->save();
    if ($document->hasErrors()) {
        printErrors('Error creating SMS trunk group', $document);

        return null;
    }
    $group = $document->getData();
    echo 'Created SMS trunk group: '.$group->getId().' - '.$group->getName().' ('.$group->getSmsTrunksCount()." trunks)\n";

    return $group;
}

function listGroups(): void
{
    echo "\n=== Existing SMS Trunk Groups ===\n";
    $groups = SmsTrunkGroup::all([
        'include' => 'sms_trunks',
        'page' => ['size' => 5, 'number' => 1],
    ])->getData();
    foreach ($groups as $g) {
        $included = $g->smsTrunks()->getIncluded();
        $names = $included ? array_map(static fn ($t) => $t->getName(), $included->all()) : [];
        echo $g->getName().' ('.$g->getSmsTrunksCount().' trunks) '.implode(', ', $names)."\n";
    }
}

$group = null;
try {
    if (2 === count($trunks)) {
        $group = groupTrunks(array_values($trunks), $suffix);
    }
    if (null !== $group) {
        listGroups();
    }
} finally {
    if (null !== $group) {
        echo "\n=== Deleting SMS Trunk Group (and its trunks) ===\n";
        deleteItem($group, 'SMS trunk group');
    } else {
        foreach ($trunks as $trunk) {
            deleteItem($trunk, 'SMS trunk');
        }
    }
}
