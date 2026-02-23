<?php

require_once 'bootstrap.php';

// fetch regions collection
$parameters = [
    'filter' => [
        'country.id' => '1f6fc2bd-f081-4202-9b1a-d9cb88d942b9',
        'name' => 'Arizona',
    ],
    'include' => 'country',
    'sort' => '-name', // name for asceding order
];
$regionsDocument = Didww\Item\Region::all($parameters);
$regions = $regionsDocument->getData();
foreach ($regions as $region) {
    var_dump(
        $region->getId(), // 8bef58e3-8e0e-43ba-8d03-1143968a6a47
        $region->getName(), // Arizona
        $region->country()->getIncluded()->getName() // United States
    );
}

// fetch the specific region
$uuid = '8bef58e3-8e0e-43ba-8d03-1143968a6a47';
$regionDocument = Didww\Item\Region::find($uuid, ['include' => 'country']);
$region = $regionDocument->getData();
var_dump(
    $region->getId(), // 8bef58e3-8e0e-43ba-8d03-1143968a6a47
    $region->getName(), // Arizona
    $region->country()->getIncluded()->getName() // United States
);
