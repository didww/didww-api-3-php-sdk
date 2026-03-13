<?php

require_once 'bootstrap.php';

// Step 1: find the NANPA prefix by NPA/NXX (e.g. 201-221)
$nanpaPrefixes = Didww\Item\NanpaPrefix::all([
    'filter' => ['npanxx' => '201221'],
    'page' => ['size' => 1, 'number' => 1],
])->getData()->all();

if (empty($nanpaPrefixes)) {
    exit("NANPA prefix 201-221 not found\n");
}

$nanpaPrefix = $nanpaPrefixes[0];
echo 'NANPA prefix: '.$nanpaPrefix->getId().' NPA='.$nanpaPrefix->getNPA().' NXX='.$nanpaPrefix->getNXX()."\n";

// Step 2: find a DID group for this prefix and load its SKUs
$didGroups = Didww\Item\DidGroup::all([
    'filter' => ['nanpa_prefix.id' => $nanpaPrefix->getId()],
    'include' => 'stock_keeping_units',
    'page' => ['size' => 1, 'number' => 1],
])->getData()->all();

if (empty($didGroups)) {
    exit("No DID group found for this NANPA prefix\n");
}

$skus = $didGroups[0]->stockKeepingUnits()->getIncluded()->all();
if (empty($skus)) {
    exit("No SKUs found for this DID group\n");
}

$skuId = $skus[0]->getId();
echo 'DID group: '.$didGroups[0]->getId()."  SKU: $skuId\n";

// Step 3: create the order
$order = new Didww\Item\Order([
    'allow_back_ordering' => true,
    'items' => [
        new Didww\Item\OrderItem\Did([
            'sku_id' => $skuId,
            'nanpa_prefix_id' => $nanpaPrefix->getId(),
            'qty' => 1,
        ]),
    ],
]);

$created = $order->save()->getData();

echo 'Order '.$created->getId()
    .' amount='.$created->getAmount()
    .' status='.$created->getStatus()->value
    .' ref='.$created->getReference()."\n";
