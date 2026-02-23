<?php

require_once 'bootstrap.php';

// fetch DID groups collection
$parameters = [
    'filter' => [
        'country.id' => '1f6fc2bd-f081-4202-9b1a-d9cb88d942b9',
        'area_name' => 'Beverly Hills',
    ],
    'include' => 'stock_keeping_units',
];
$didGroupsDocument = Didww\Item\DidGroup::all($parameters);
$didGroups = $didGroupsDocument->getData();

var_dump(count($didGroups)); // 2

foreach ($didGroups as $didGroup) {
    var_dump(
        $didGroup->getId(), // df73511e-3b8e-4967-9bd8-d7b88ae1a084
        $didGroup->getAreaName(), // Beverly Hills
        $didGroup->getPrefix(), // 352, 310
        $didGroup->getFeatures(), // ['voice']
        $didGroup->getIsMetered(), // false
        $didGroup->getAllowAdditionalChannels(), // true
        count($didGroup->stockKeepingUnits()->getIncluded()->all()) // 2
    );
    $stockKeepingUnit = $didGroup->stockKeepingUnits()->getIncluded()->all()[0];
    var_dump(
        $stockKeepingUnit->getId(), // 82460535-2b3f-43a6-bcdd-62f3da0d9fa6
        $stockKeepingUnit->getSetupPrice(), // 0
        $stockKeepingUnit->getMonthlyPrice(), // 0.09
        $stockKeepingUnit->getChannelsIncludedCount() // 0
    );
}

// fetch the specific DID group
$uuid = 'df73511e-3b8e-4967-9bd8-d7b88ae1a084';
$didGroupDocument = Didww\Item\DidGroup::find($uuid, ['include' => 'stock_keeping_units']);
$didGroup = $didGroupDocument->getData();
var_dump(
    $didGroup->getId(), // df73511e-3b8e-4967-9bd8-d7b88ae1a084
    $didGroup->getAreaName(), // Beverly Hills
    $didGroup->getPrefix(), // 352, 310
    $didGroup->getFeatures(), // ['voice']
    $didGroup->getIsMetered(), // false
    $didGroup->getAllowAdditionalChannels(), // true
    count($didGroup->stockKeepingUnits()->getIncluded()->all()) // 2
);
$stockKeepingUnit = $didGroup->stockKeepingUnits()->getIncluded()->all()[0];
var_dump(
    $stockKeepingUnit->getId(), // 82460535-2b3f-43a6-bcdd-62f3da0d9fa6
    $stockKeepingUnit->getSetupPrice(), // 0
    $stockKeepingUnit->getMonthlyPrice(), // 0.09
    $stockKeepingUnit->getChannelsIncludedCount() // 0
);
