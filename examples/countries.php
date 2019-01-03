<?php

require_once 'bootstrap.php';

// fetch countries collection
$parameters = [
    'filter' => [
        'prefix' => '1'
    ],
    'sort' => 'name' // -name for desceding order
];
$countriesDocument = \Didww\Item\Country::all($parameters);
$countries = $countriesDocument->getData();
foreach($countries as $country) {
    var_dump(
        $country->getId(), // US example: 1f6fc2bd-f081-4202-9b1a-d9cb88d942b9
        $country->getName(), // Canada, Dominican Republic, Jamaica, Puerto Rico, Saint Vincent, United States
        $country->getPrefix(), // 1
        $country->getIso() // CA, DO, JM, PR, VC, US
    );
}

// fetch the specific country
$usUuid = '1f6fc2bd-f081-4202-9b1a-d9cb88d942b9';
$countryDocument = \Didww\Item\Country::find($usUuid);
$country = $countryDocument->getData();
var_dump(
    $country->getId(), // 1f6fc2bd-f081-4202-9b1a-d9cb88d942b9
    $country->getName(), // United States
    $country->getPrefix(), // 1
    $country->getIso() // US
);
