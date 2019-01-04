<?php

require_once 'bootstrap.php';

$availableDidsDocument = \Didww\Item\AvailableDid::all([
    'include' => 'did_group.stock_keeping_units',
]);
$availableDids = $availableDidsDocument->getData()->all();
$availableDid = $availableDids[array_rand($availableDids)];
var_dump(
    $availableDid->getNumber() // 14806858034
);
$didGroupRelation = $availableDid->didGroup();
$stockKeepingUnitsRelation = $didGroupRelation->getIncluded()->stockKeepingUnits();
$skus = $stockKeepingUnitsRelation->getIncluded()->all();
$sku = $skus[array_rand($skus)];
var_dump($sku->getId()); // be1d31ce-c317-4a8a-85bd-5fe3915d4524

$orderItem = new \Didww\Item\OrderItem\AvailableDid();
$orderItem->setAvailableDidId($availableDid->getId());
$orderItem->setSkuId($sku->getId());

$order = new \Didww\Item\Order();
$items = [
    $orderItem,
];
$order->setItems($items);
$orderDocument = $order->save();
$order = $orderDocument->getData();

var_dump(count($order->getItems())); // 1
