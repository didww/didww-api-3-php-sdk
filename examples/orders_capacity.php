<?php

// Buys capacity: orders shared channels in a capacity pool. Capacity items
// cannot share an order with DID items.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/orders_capacity.php

require_once 'bootstrap.php';

// get capacity pool
$capacityPoolsDocument = Didww\Item\CapacityPool::all();
$capacityPools = $capacityPoolsDocument->getData()->all();
$capacityPool = $capacityPools[array_rand($capacityPools)];
var_dump($capacityPool->getName()); // Extended

// purchase capacity
$orderItem = new Didww\Item\OrderItem\Capacity();
$orderItem->setCapacityPool($capacityPool);
$orderItem->setQty(1);

$order = new Didww\Item\Order();
$items = [
    $orderItem,
];
$order->setItems($items);
$orderDocument = $order->save();
$order = $orderDocument->getData();

var_dump(count($order->getItems())); // 1
