<?php

// Create an order with emergency order items (2026-04-16).

require_once 'bootstrap.php';

use Didww\Item\Order;
use Didww\Item\OrderItem\Emergency;

echo "=== Creating Emergency Order ===\n";
$suffix = bin2hex(random_bytes(4));

$emergencyItem = new Emergency([
    'qty' => 1,
    'emergency_calling_service_id' => 'your-emergency-calling-service-id',
]);

$order = new Order();
$order->setItems([$emergencyItem]);
$order->setExternalReferenceId("php-emergency-$suffix");

$document = $order->save();

if ($document->hasErrors()) {
    echo "Error creating order:\n";
    var_dump($document->getErrors());
} else {
    $order = $document->getData();
    echo "Created order: " . $order->getId() . "\n";
    echo "  Reference: " . $order->getReference() . "\n";
    echo "  Status: " . $order->getStatus()->value . "\n";
    echo "  Amount: " . $order->getAmount() . "\n";
    echo "  External Reference ID: " . ($order->getExternalReferenceId() ?? 'null') . "\n";
}
