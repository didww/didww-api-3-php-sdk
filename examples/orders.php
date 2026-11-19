<?php

// Orders: lists recent orders, orders a DID by SKU and cancels that order by
// deleting it. Only a pending order can be canceled; the API refuses to
// cancel an order that is already completed.
// 2026-04-16 adds external_reference_id for customer tagging.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/orders.php

require_once 'bootstrap.php';

use Didww\Item\DidGroup;
use Didww\Item\Order;
use Didww\Item\OrderItem\Did as DidOrderItem;

function printErrors(string $message, $document): void
{
    echo $message.":\n";
    foreach ($document->getErrors() as $error) {
        echo '  - '.($error->getDetail() ?? $error->getTitle())."\n";
    }
}

echo "=== Recent Orders ===\n";
$orders = Order::all([
    'sort' => '-created_at',
    'page' => ['size' => 5, 'number' => 1],
])->getData();
echo 'Found '.count($orders)." orders\n";
foreach ($orders as $order) {
    $itemTypes = array_map(fn ($item) => $item->getType(), $order->getItems());
    echo 'Order '.$order->getReference().' ('.$order->getId().'): '.$order->getStatus()->value.', amount '.$order->getAmount()."\n";
    echo '  Items: '.implode(', ', $itemTypes)."\n";
    if (null !== $order->getExternalReferenceId()) {
        echo '  External reference: '.$order->getExternalReferenceId()."\n";
    }
}

echo "\n=== Ordering a DID by SKU ===\n";
// A DID group with numbers in stock, as the order is not back-ordered
$didGroups = DidGroup::all([
    'filter' => ['is_available' => 'true'],
    'include' => 'stock_keeping_units',
    'page' => ['size' => 10, 'number' => 1],
])->getData();
$sku = null;
foreach ($didGroups as $didGroup) {
    $skus = $didGroup->stockKeepingUnits()->getIncluded();
    if ($skus->isNotEmpty()) {
        $sku = $skus->first();
        echo 'DID group: '.$didGroup->getAreaName().' ('.$didGroup->getPrefix().'), SKU: '.$sku->getId()."\n";
        break;
    }
}
if (null === $sku) {
    echo "No DID group with stock keeping units found\n";
    exit(1);
}

$suffix = bin2hex(random_bytes(4));
$order = new Order([
    'allow_back_ordering' => false,
    'items' => [
        new DidOrderItem(['sku_id' => $sku->getId(), 'qty' => 1]),
    ],
]);
$order->setExternalReferenceId("php-order-$suffix");
$document = $order->save();
if ($document->hasErrors()) {
    printErrors('Error creating the order', $document);
    exit(1);
}
$order = $document->getData();
echo 'Created order '.$order->getReference().' ('.$order->getId().'): '.$order->getStatus()->value."\n";
echo '  Amount: '.$order->getAmount()."\n";
echo '  External reference: '.($order->getExternalReferenceId() ?? 'null')."\n";

echo "\n=== Canceling the Order ===\n";
$document = $order->delete();
if ($document->hasErrors()) {
    printErrors('Error canceling the order', $document);
} else {
    echo "Order canceled\n";
}
