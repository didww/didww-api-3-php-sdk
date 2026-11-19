<?php

// Places ONE order with the three DID order item variants:
// 1. Didww\Item\OrderItem\Did: a SKU and a quantity (DIDWW picks the numbers)
// 2. Didww\Item\OrderItem\AvailableDid: a specific available DID and its SKU
// 3. Didww\Item\OrderItem\ReservationDid: a DID reserved beforehand and its SKU
// All three are did_order_items on the wire, so they fit in one order.
// Capacity items (Didww\Item\OrderItem\Capacity) cannot share an order with
// DID items: the API answers "items - should have same type". Order capacity
// separately (see orders_capacity.php).
// Then lists the DIDs of the order.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/orders_all_item_types.php

require_once 'bootstrap.php';

use Didww\Item\AvailableDid;
use Didww\Item\Did;
use Didww\Item\DidGroup;
use Didww\Item\DidReservation;
use Didww\Item\Order;
use Didww\Item\OrderItem\AvailableDid as AvailableDidOrderItem;
use Didww\Item\OrderItem\Did as DidOrderItem;
use Didww\Item\OrderItem\ReservationDid as ReservationDidOrderItem;

function printErrors(string $message, $document): void
{
    echo $message.":\n";
    foreach ($document->getErrors() as $error) {
        echo '  - '.($error->getDetail() ?? $error->getTitle())."\n";
    }
}

// The first SKU of the DID group included with an available DID, or null.
function skuIdOf(AvailableDid $availableDid): ?string
{
    $skus = $availableDid->didGroup()->getIncluded()?->stockKeepingUnits()->getIncluded();

    return $skus?->first()?->getId();
}

echo "=== Finding Available DIDs ===\n";
$availableDids = AvailableDid::all([
    'include' => 'did_group.stock_keeping_units',
    'page' => ['size' => 2, 'number' => 1],
])->getData();
if (count($availableDids) < 2) {
    echo "Need at least 2 available DIDs for this example\n";
    exit(1);
}
$available = $availableDids[0];
$toReserve = $availableDids[1];
$availableSkuId = skuIdOf($available);
$reservedSkuId = skuIdOf($toReserve);
if (null === $availableSkuId || null === $reservedSkuId) {
    echo "No stock keeping units for the available DIDs\n";
    exit(1);
}
echo 'Available DID to order: '.$available->getNumber().', SKU '.$availableSkuId."\n";
echo 'Available DID to reserve: '.$toReserve->getNumber().', SKU '.$reservedSkuId."\n";

echo "\n=== Finding a SKU to Order by Quantity ===\n";
// A DID group with numbers in stock, as the order is not back-ordered
$didGroups = DidGroup::all([
    'filter' => ['is_available' => 'true'],
    'include' => 'stock_keeping_units',
    'page' => ['size' => 10, 'number' => 1],
])->getData();
$skuForQty = null;
foreach ($didGroups as $didGroup) {
    $skus = $didGroup->stockKeepingUnits()->getIncluded();
    if ($skus->isNotEmpty()) {
        $skuForQty = $skus->first();
        echo 'SKU '.$skuForQty->getId().' of DID group '.$didGroup->getId()."\n";
        break;
    }
}
if (null === $skuForQty) {
    echo "No DID group with stock keeping units found\n";
    exit(1);
}

echo "\n=== Reserving a DID ===\n";
$reservation = new DidReservation();
$reservation->setAvailableDid(AvailableDid::build($toReserve->getId()));
$reservation->setDescription('PHP all item types example');
$document = $reservation->save();
if ($document->hasErrors()) {
    printErrors('Error reserving the DID', $document);
    exit(1);
}
$reservation = $document->getData();
echo 'Reservation: '.$reservation->getId()."\n";

$order = null;
try {
    echo "\n=== Creating One Order with All Three DID Item Variants ===\n";
    $byQty = new DidOrderItem(['sku_id' => $skuForQty->getId(), 'qty' => 1]);

    $byAvailableDid = new AvailableDidOrderItem();
    $byAvailableDid->setSkuId($availableSkuId);
    $byAvailableDid->setAvailableDidId($available->getId());

    $byReservation = new ReservationDidOrderItem();
    $byReservation->setSkuId($reservedSkuId);
    $byReservation->setDidReservationId($reservation->getId());

    $newOrder = new Order();
    $newOrder->setItems([$byQty, $byAvailableDid, $byReservation]);
    $document = $newOrder->save();
    if ($document->hasErrors()) {
        printErrors('Error creating the order', $document);
    } else {
        $order = $document->getData();
        echo 'Order '.$order->getReference().' ('.$order->getId().'): '.$order->getStatus()->value."\n";
        echo '  Amount: '.$order->getAmount()."\n";
        echo '  Created at: '.$order->getCreatedAt()->format('Y-m-d H:i:s')."\n";
        foreach ($order->getItems() as $index => $item) {
            echo '  Item '.($index + 1).': '.$item->getType().', qty '.$item->getQty()."\n";
        }
    }
} finally {
    // The order consumes the reservation; without an order it is deleted
    if (null === $order) {
        $document = $reservation->delete();
        echo $document->hasErrors() ? "Error deleting the reservation\n" : "Reservation deleted\n";
    }
}

if (null !== $order) {
    echo "\n=== DIDs of the Order ===\n";
    $dids = Did::all(['filter' => ['order.id' => $order->getId()]])->getData();
    echo 'Found '.count($dids)." DIDs (a pending order has none yet)\n";
    foreach ($dids as $did) {
        echo '  '.$did->getNumber().' ('.$did->getId().")\n";
    }
}
