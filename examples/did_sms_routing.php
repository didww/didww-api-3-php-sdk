<?php

// Routes a DID's inbound SMS to an SMS trunk, then to an SMS trunk group, and
// restores the DID's original SMS routing at the end, also when a step fails.
// The trunk and the group are created by this script and deleted at the end.
//
// The SMS trunk and the SMS trunk group of a DID are exclusive: setSmsTrunk()
// sends sms_trunk_group as null in the same PATCH and setSmsTrunkGroup() sends
// sms_trunk as null, just like the voice in trunk and voice in trunk group.
// Passing null to both unroutes the DID. An SMS trunk or group cannot be
// deleted while DIDs are routed to it.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/did_sms_routing.php

require_once 'bootstrap.php';

use Didww\Enum\SmsHttpMethod;
use Didww\Item\Did;
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

// Saves $item; returns the saved item, or null after printing the errors.
function saveOrReport($item, string $what, array $parameters = [])
{
    $document = $item->save($parameters);
    if ($document->hasErrors()) {
        printErrors("Error $what", $document);

        return null;
    }

    return $document->getData();
}

function printSmsRouting(Did $did): void
{
    $trunk = $did->smsTrunk()->getIncluded();
    $group = $did->smsTrunkGroup()->getIncluded();
    echo '   sms_trunk       = '.($trunk ? $trunk->getName() : 'null')."\n";
    echo '   sms_trunk_group = '.($group ? $group->getName() : 'null')."\n";
}

// Changes the SMS routing of the DID with $change and prints the result.
function route(string $didId, callable $change, string $what): bool
{
    $did = Did::build($didId);
    $change($did);
    $updated = saveOrReport($did, $what, ['include' => 'sms_trunk,sms_trunk_group']);
    if (null !== $updated) {
        printSmsRouting($updated);
    }

    return null !== $updated;
}

function deleteItem($item, string $what): void
{
    $document = $item->delete();
    if ($document->hasErrors()) {
        printErrors("Error deleting $what ".$item->getId(), $document);
    } else {
        echo ucfirst($what).' '.$item->getName()." deleted\n";
    }
}

echo "=== Finding DID ===\n";
$dids = Did::all([
    'include' => 'sms_trunk,sms_trunk_group',
    'page' => ['size' => 1, 'number' => 1],
])->getData();
if (0 === count($dids)) {
    exit("No DIDs found. Please order a DID first.\n");
}
$did = $dids[0];
$originalTrunk = $did->smsTrunk()->getIncluded();
$originalGroup = $did->smsTrunkGroup()->getIncluded();
echo 'Using DID: '.$did->getNumber().' ('.$did->getId().")\n";
printSmsRouting($did);

$suffix = bin2hex(random_bytes(4));

$trunk = $groupTrunk = $group = null;
$changed = false;
try {
    echo "\n=== Creating an SMS trunk and an SMS trunk group ===\n";
    $email = new SmsToEmail();
    $email->setSendTo('sms@example.com');
    $email->setSubject('Inbound SMS');
    $email->setMessage('You have a new message.');
    $newTrunk = new SmsTrunk();
    $newTrunk->setName("PHP routing $suffix");
    $newTrunk->setConfiguration($email);
    $trunk = saveOrReport($newTrunk, 'creating the SMS trunk');

    $webhook = new HttpIn();
    $webhook->setUrl('https://example.com/sms');
    $webhook->setHttpMethod(SmsHttpMethod::GET);
    $webhook->setQueryParameters(['text' => '{SMS_TEXT}', 'from' => '{SMS_SRC_ADDR}']);
    $newTrunk = new SmsTrunk();
    $newTrunk->setName("PHP group member $suffix");
    $newTrunk->setConfiguration($webhook);
    $groupTrunk = saveOrReport($newTrunk, 'creating the SMS trunk of the group');

    if (null !== $groupTrunk) {
        $newGroup = new SmsTrunkGroup();
        $newGroup->setName("PHP routing group $suffix");
        $newGroup->setSmsTrunks(new Collection([SmsTrunk::build($groupTrunk->getId())]));
        $group = saveOrReport($newGroup, 'creating the SMS trunk group');
    }

    if (null !== $trunk) {
        echo "\n=== 1. Assigning the SMS trunk (sends sms_trunk_group as null) ===\n";
        $changed = true;
        if (route($did->getId(), fn (Did $d) => $d->setSmsTrunk(SmsTrunk::build($trunk->getId())), 'assigning the SMS trunk')) {
            echo "\n=== DIDs routed to ".$trunk->getName()." ===\n";
            foreach (Did::all(['filter' => ['sms_trunk.id' => $trunk->getId()]])->getData() as $routed) {
                echo '   '.$routed->getNumber()."\n";
            }
        }
    }

    if (null !== $group) {
        echo "\n=== 2. Assigning the SMS trunk group (sends sms_trunk as null) ===\n";
        $changed = true;
        route($did->getId(), fn (Did $d) => $d->setSmsTrunkGroup(SmsTrunkGroup::build($group->getId())), 'assigning the SMS trunk group');
    }
} finally {
    if ($changed) {
        echo "\n=== 3. Restoring the original SMS routing ===\n";
        route($did->getId(), function (Did $d) use ($originalTrunk, $originalGroup) {
            if ($originalTrunk) {
                $d->setSmsTrunk(SmsTrunk::build($originalTrunk->getId()));
            } elseif ($originalGroup) {
                $d->setSmsTrunkGroup(SmsTrunkGroup::build($originalGroup->getId()));
            } else {
                // Unroutes the DID: sends sms_trunk and sms_trunk_group as null.
                $d->setSmsTrunk(null);
                $d->setSmsTrunkGroup(null);
            }
        }, 'restoring the SMS routing');
    }

    echo "\n=== Cleaning up ===\n";
    if (null !== $group) {
        // Deleting a group also deletes the trunks in it.
        deleteItem($group, 'SMS trunk group');
    } elseif (null !== $groupTrunk) {
        deleteItem($groupTrunk, 'SMS trunk');
    }
    if (null !== $trunk) {
        deleteItem($trunk, 'SMS trunk');
    }
}
