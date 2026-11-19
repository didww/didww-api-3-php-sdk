<?php

// Reads resources with included relationships: regions with their country,
// DID groups with their SKUs, available DIDs with nested includes and DIDs
// with their voice in trunk.
//
// A relationship named in 'include' comes with the resource: getIncluded()
// returns the related item (a to-one relationship) or a Collection of items
// (a to-many relationship), with their attributes. A relationship that is not
// included carries only links: getIncluded() returns null (an empty
// Collection for a to-many relationship), hasData() is false, and the related
// item is read from its own endpoint.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/includes.php

require_once 'bootstrap.php';

use Didww\Item\AvailableDid;
use Didww\Item\Did;
use Didww\Item\DidGroup;
use Didww\Item\Region;

$page = ['size' => 3, 'number' => 1];

echo "=== Regions with country ===\n";
$regions = Region::all(['include' => 'country', 'page' => $page])->getData();
foreach ($regions as $region) {
    $country = $region->country()->getIncluded();
    echo '  '.$region->getName().' - country: '.($country ? $country->getName() : 'none')."\n";
}

echo "\n=== DID groups with stock_keeping_units ===\n";
$didGroups = DidGroup::all(['include' => 'stock_keeping_units', 'page' => $page])->getData();
foreach ($didGroups as $didGroup) {
    $skus = $didGroup->stockKeepingUnits()->getIncluded();
    echo '  '.$didGroup->getPrefix().' '.$didGroup->getAreaName().' - '.count($skus)." SKU(s)\n";
    foreach ($skus as $sku) {
        echo '    '.$sku->getMonthlyPrice().'/month, '.$sku->getChannelsIncludedCount()." channels included\n";
    }
}

echo "\n=== Available DIDs with did_group.stock_keeping_units ===\n";
$availableDids = AvailableDid::all(['include' => 'did_group.stock_keeping_units', 'page' => $page])->getData();
foreach ($availableDids as $availableDid) {
    // the nested include fills the relationships of the included DID group
    $didGroup = $availableDid->didGroup()->getIncluded();
    $skus = $didGroup ? $didGroup->stockKeepingUnits()->getIncluded() : null;
    echo '  '.$availableDid->getNumber().' - group: '.($didGroup ? $didGroup->getPrefix().' '.$didGroup->getAreaName() : 'none');
    echo ', '.($skus ? count($skus) : 0)." SKU(s)\n";
}

echo "\n=== DIDs with voice_in_trunk ===\n";
$dids = Did::all(['include' => 'voice_in_trunk', 'page' => $page])->getData();
foreach ($dids as $did) {
    $trunk = $did->voiceInTrunk()->getIncluded();
    // hasData() is true for an included relationship, also when it is empty
    echo '  '.$did->getNumber().' - trunk: '.($trunk ? $trunk->getName() : '(none)');
    echo ', included: '.var_export($did->voiceInTrunk()->hasData(), true)."\n";
}

echo "\n=== The same DIDs without include ===\n";
$dids = Did::all(['page' => $page])->getData();
foreach ($dids as $did) {
    $relationship = $did->voiceInTrunk();
    $links = $relationship->getLinks();
    echo '  '.$did->getNumber().' - getIncluded(): '.var_export($relationship->getIncluded(), true);
    echo ', hasData(): '.var_export($relationship->hasData(), true)."\n";
    if (null !== $links && isset($links->related)) {
        echo '    read it from '.$links->related->getHref()."\n";
    }
}
