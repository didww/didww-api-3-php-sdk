<?php

require_once 'bootstrap.php';

// to get different sku_id see examples\did_groups.php
$orderItemAttributes = [
    'sku_id' => '82460535-2b3f-43a6-bcdd-62f3da0d9fa6',
    'qty' => 2,
];
$orderAttributes = [
    'items' => [
        new Didww\Item\OrderItem\Did($orderItemAttributes),
    ],
];
$order = new Didww\Item\Order($orderAttributes);
$orderDocument = $order->save();
$order = $orderDocument->getData();

var_dump(count($order->getItems())); // 1

$orderItem = $order->getItems()[0];
var_dump(
    $order->getId(), // 2989925f-7df3-4dc9-9944-d1fa0f4e8e86
    $order->getAmount(), // 0.18
    $order->getStatus(), // Pending
    $order->getCreatedAt(), // new \DateTime('2019-01-03 15:46:27')
    $order->getDescription(), // DID
    $order->getReference(), // XXU-610373
    $orderItem->getQty(), // 2
    $orderItem->getNrc(), // 0.0
    $orderItem->getMrc(), // 0.09
    $orderItem->getBilledFrom(), // null
    $orderItem->getBilledTo(), // null
    $orderItem->getProratedMrc(), // false
    $orderItem->getDidGroupId() // df73511e-3b8e-4967-9bd8-d7b88ae1a084
);
