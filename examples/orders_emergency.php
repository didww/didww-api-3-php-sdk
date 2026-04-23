<?php

// Inspects Emergency orders (2026-04-16).
//
// Emergency orders are created server-side when an EmergencyCallingService
// is activated or renewed — customers cannot POST them directly.

require_once 'bootstrap.php';

use Didww\Item\EmergencyCallingService;
use Didww\Item\Order;

echo "=== Recent Orders (filtering for Emergency) ===\n";
$orders = Order::all([
    'sort' => '-created_at',
    'page' => ['size' => 50, 'number' => 1],
])->getData();
$emergencyOrders = [];
foreach ($orders as $order) {
    $items = $order->getItems();
    if ($items) {
        foreach ($items as $item) {
            $type = is_object($item) && method_exists($item, 'getType') ? $item->getType() : ($item['type'] ?? null);
            if ('emergency_order_items' === $type) {
                $emergencyOrders[] = $order;
                break;
            }
        }
    }
}
echo 'Found '.count($emergencyOrders).' emergency orders out of '.count($orders)." total\n";

foreach (array_slice($emergencyOrders, 0, 5) as $order) {
    echo "\nOrder: ".$order->getId()."\n";
    echo '  Reference: '.$order->getReference()."\n";
    echo '  Status: '.$order->getStatus()->value."\n";
    echo '  Amount: '.$order->getAmount()."\n";
    echo '  Created: '.$order->getCreatedAt()->format('Y-m-d H:i:s')."\n";
}

// Follow the link from an EmergencyCallingService to its order
echo "\n=== Emergency Calling Service -> Order ===\n";
$document = EmergencyCallingService::all(['include' => 'order', 'page' => ['size' => 1, 'number' => 1]]);
$services = $document->getData();
$service = count($services) > 0 ? $services[0] : null;
if ($service) {
    echo 'ECS '.$service->getId().' ('.$service->getName().")\n";
    $order = $service->order()->getIncluded();
    if ($order) {
        echo '  -> Order '.$order->getId().' — status: '.$order->getStatus()->value.', amount: '.$order->getAmount()."\n";
    } else {
        echo "  -> No order linked yet\n";
    }
} else {
    echo "No emergency_calling_services on this account\n";
}
