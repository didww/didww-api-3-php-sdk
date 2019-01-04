<?php

require_once 'bootstrap.php';

$capacityGroup = new \Didww\Item\SharedCapacityGroup();
// set name (should be unique)
$capacityGroup->setName('My New Channel Group '.uniqid());

$capacityPool = \Didww\Item\CapacityPool::all()->getData()[0];
$capacityGroup->setMeteredhannelsCount(10);
// see capacity order example to purchase shared channels and assign them to pool
$capacityGroup->setSharedChannelsCount(1);
// set capacity pool as a source of purchased channels
$capacityGroup->setCapacityPool($capacityPool);

$capacityGroupDocument = $capacityGroup->save();

if ($capacityGroupDocument->hasErrors()) {
    var_dump($capacityGroupDocument->getErrors());
} else {
    $capacityGroup = $capacityGroupDocument->getData();
    var_dump(
      $capacityGroup->getId(), // 1f6fc2bd-f081-4202-9b1a-d9cb88d942b9
      $capacityGroup->getName(), // "My New Channel Group 5c2f27fa320f2"
      $capacityGroup->getCreatedAt(), // object(DateTime)
      $capacityGroup->getMeteredChannelsCount(),// int(10),
      $capacityGroup->getSharedChannelsCount() // int(1)
  );
}
