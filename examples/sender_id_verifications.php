<?php

// Lists Sender ID verifications with their SMS trunks, renames an active one
// and routes its outbound messages through an SMS trunk that sends them, then
// restores its original name and SMS trunk, also when a step fails.
//
// A verification is created and reviewed outside the API; only its name and
// its SMS trunk can be changed, and a closed one cannot be changed at all.
// Only http_out, smpp_esme and smpp_smsc trunks are accepted.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/sender_id_verifications.php

require_once 'bootstrap.php';

use Didww\Enum\SenderIdVerificationStatus;
use Didww\Item\SenderIdVerification;
use Didww\Item\SmsTrunk;

function wire($value): string
{
    return $value instanceof BackedEnum ? $value->value : (string) $value;
}

function printVerification(SenderIdVerification $v): void
{
    $trunk = $v->smsTrunk()->getIncluded();
    echo $v->getReference().' '.$v->getName().' ('.wire($v->getSenderType()).', '.wire($v->getStatus()).'): '
        .implode(', ', $v->getSenderIds()).' via '.($trunk ? $trunk->getName() : 'no SMS trunk')."\n";
    if ($v->isChangesRequired()) {
        echo '  changes required: '.implode('; ', $v->getRejectReasons() ?? []).' '.($v->getRejectComment() ?? '')."\n";
    }
    $meta = $v->getMeta();
    if ($meta) {
        $meta = $meta->toArray();
        echo '  setup price: '.($meta['setup_price'] ?? 'N/A').', monthly price: '.($meta['monthly_price'] ?? 'N/A')
            .', renews: '.($v->getRenewDate() ?? 'N/A')."\n";
    }
}

// Sends a PATCH with only what $change sets; returns whether it was accepted.
function update(string $id, callable $change, string $what): bool
{
    $verification = SenderIdVerification::build($id);
    $change($verification);
    $document = $verification->save();
    if ($document->hasErrors()) {
        echo "Error $what:\n";
        foreach ($document->getErrors() as $error) {
            echo '  - '.($error->getDetail() ?? $error->getTitle())."\n";
        }

        return false;
    }

    return true;
}

echo "=== Sender ID Verifications ===\n";
$verifications = SenderIdVerification::all(['include' => 'sms_trunk'])->getData();
$verification = null;
foreach ($verifications as $v) {
    printVerification($v);
    if (null === $verification && $v->isActive()) {
        $verification = $v;
    }
}
if (null === $verification) {
    exit("No active Sender ID verification\n");
}

$id = $verification->getId();
$originalName = $verification->getName();
$originalTrunk = $verification->smsTrunk()->getIncluded();

echo "\n=== Renaming ".$verification->getReference()." ===\n";
try {
    if (update($id, fn ($v) => $v->setName($originalName.' (renamed)'), 'renaming the verification')) {
        echo 'Name: '.SenderIdVerification::find($id)->getData()->getName()."\n";
    }
} finally {
    if (update($id, fn ($v) => $v->setName($originalName), 'restoring the name')) {
        echo "Name restored: $originalName\n";
    }
}

$sendingTrunk = null;
foreach (SmsTrunk::all()->getData() as $trunk) {
    if ($trunk->getAllowOut()) {
        $sendingTrunk = $trunk;
        break;
    }
}
if (null === $sendingTrunk) {
    exit("No SMS trunk that sends messages\n");
}

echo "\n=== Routing ".$verification->getReference().' through '.$sendingTrunk->getName()." ===\n";
try {
    if (update($id, fn ($v) => $v->setSmsTrunk(SmsTrunk::build($sendingTrunk->getId())), 'routing the verification')) {
        echo "\nActive verifications routed through this trunk:\n";
        $routed = SenderIdVerification::all(['filter' => [
            'status' => SenderIdVerificationStatus::ACTIVE->value,
            'sms_trunk.id' => $sendingTrunk->getId(),
        ]])->getData();
        foreach ($routed as $v) {
            echo '  '.$v->getReference().' '.$v->getName()."\n";
        }
    }
} finally {
    // Restore the original trunk even when a step above fails. Null unassigns
    // the trunk, which stops outbound messages until a trunk is assigned again.
    echo "\n=== Restoring the original SMS trunk ===\n";
    $restoreTo = $originalTrunk ? SmsTrunk::build($originalTrunk->getId()) : null;
    if (update($id, fn ($v) => $v->setSmsTrunk($restoreTo), 'restoring the SMS trunk')) {
        echo $originalTrunk ? 'Routed through '.$originalTrunk->getName()." again\n" : "No SMS trunk again\n";
    }
}
