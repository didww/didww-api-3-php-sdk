<?php

require_once 'bootstrap.php';

// get available DID
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

// reserve selected DID
$didReservation = new \Didww\Item\DidReservation();
$didReservation->setAvailableDid($availableDid);
$didReservation->setDescription('php sdk');
$didReservation = $didReservation->save()->getData();
var_dump(
    $didReservation->getDescription(), // php sdk
    $didReservation->getExpireAt(), // 2019-01-04 09:34:07
    $didReservation->getCreatedAt() // 2019-01-04 09:24:07
);

// purchase reserved DID
$orderItem = new \Didww\Item\OrderItem\ReservationDid();
$orderItem->setDidReservationId($didReservation->getId());
$orderItem->setSku($sku);

$order = new \Didww\Item\Order();
$items = [
    $orderItem,
];
$order->setItems($items);
$orderDocument = $order->save();
$order = $orderDocument->getData();

var_dump(count($order->getItems())); // 1
