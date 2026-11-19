<?php

// DID reservations: reserves an available DID, lists the reservations with
// their available DID included, finds the new reservation by ID and deletes
// it, also when a step fails. A reservation holds the number until it
// expires; see orders_reservation_dids.php to order a reserved DID.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/did_reservations.php

require_once 'bootstrap.php';

use Didww\Item\AvailableDid;
use Didww\Item\DidReservation;

function printErrors(string $message, $document): void
{
    echo $message.":\n";
    foreach ($document->getErrors() as $error) {
        echo '  - '.($error->getDetail() ?? $error->getTitle())."\n";
    }
}

$availableDids = AvailableDid::all(['page' => ['size' => 1, 'number' => 1]])->getData();
if (0 === count($availableDids)) {
    exit("No available DIDs found\n");
}
$availableDid = $availableDids[0];
echo 'Reserving DID: '.$availableDid->getNumber()."\n";

echo "\n=== Creating Reservation ===\n";
$reservation = new DidReservation();
$reservation->setDescription('PHP SDK example reservation');
$reservation->setAvailableDid(AvailableDid::build($availableDid->getId()));
$document = $reservation->save();
if ($document->hasErrors()) {
    printErrors('Error reserving the DID', $document);
    exit(1);
}
// save() returns the reservation the API created, with its ID
$reservation = $document->getData();

try {
    echo 'Created reservation: '.$reservation->getId()."\n";
    echo '  Description: '.$reservation->getDescription()."\n";
    echo '  Expires at: '.($reservation->getExpiresAt()?->format('Y-m-d H:i:s') ?? 'null')."\n";

    echo "\n=== Reservations (expiring first) ===\n";
    $reservations = DidReservation::all([
        'include' => 'available_did',
        'sort' => 'expires_at',
    ])->getData();
    echo 'Found '.count($reservations)." reservations\n";
    foreach ($reservations as $listed) {
        $listedDid = $listed->availableDid()->getIncluded();
        echo '  '.$listed->getId().' - '.($listedDid ? $listedDid->getNumber() : 'unknown').', expires '.($listed->getExpiresAt()?->format('Y-m-d H:i:s') ?? 'null')."\n";
    }

    echo "\n=== Find Reservation ===\n";
    $found = DidReservation::find($reservation->getId(), ['include' => 'available_did'])->getData();
    $foundDid = $found->availableDid()->getIncluded();
    echo 'Found reservation '.$found->getId().' for '.($foundDid ? $foundDid->getNumber() : 'unknown')."\n";
} finally {
    echo "\n=== Deleting Reservation ===\n";
    $document = $reservation->delete();
    if ($document->hasErrors()) {
        printErrors('Error deleting reservation '.$reservation->getId(), $document);
    } else {
        echo "Reservation deleted\n";
    }
}
