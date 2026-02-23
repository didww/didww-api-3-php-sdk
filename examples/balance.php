<?php

require_once 'bootstrap.php';

$balanceDocument = Didww\Item\Balance::find();
$balance = $balanceDocument->getData();
var_dump(
    $balance->getTotalBalance(), // double(1000.00)
    $balance->getBalance(), // double(900.00)
    $balance->getCredit() // double(100.00)
);
