<?php

require_once 'bootstrap.php';

// fetch DID groups collection
$parameters = [
    'filter' => [
        'country.id' => '1f6fc2bd-f081-4202-9b1a-d9cb88d942b9',
        'features' => 'voice'
    ],
    'include' => 'stock_keeping_units'
];
$didGroupsDocument = \Didww\Item\DidGroup::all($parameters);
$didGroups = $didGroupsDocument->getData();
foreach($didGroups as $didGroup) {
    var_dump(
        $didGroup,
        $didGroup->getId() // 8bef58e3-8e0e-43ba-8d03-1143968a6a47
        // $didGroup->getName(), // Arizona
        // $didGroup->stockKeepingUnits()->getIncluded()->all() // United States
    );
}

// fetch the specific region
// $uuid = '';
// $regionDocument = \Didww\Item\Region::find($uuid, ['include' => 'country']);
// $didGroup = $regionDocument->getData();
// var_dump(
//     $didGroup->getId(), // 8bef58e3-8e0e-43ba-8d03-1143968a6a47
//     $didGroup->getName(), // Arizona
//     $didGroup->country()->getIncluded()->getName() # United States
// );
