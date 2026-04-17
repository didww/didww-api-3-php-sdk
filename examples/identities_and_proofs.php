<?php

// Lists identities, addresses, and proofs. Demonstrates compliance workflow.
// 2026-04-16 adds birth_country relationship on Identity.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/identities_and_proofs.php

require_once 'bootstrap.php';

use Didww\Item\Address;
use Didww\Item\Identity;
use Didww\Item\ProofType;

// List identities (include country + birth_country, 2026-04-16 adds birth_country)
echo "=== Identities ===\n";
$document = Identity::all(['include' => 'country,birth_country']);
$identities = $document->getData();
$foundPrefix = 'Found ';
echo $foundPrefix.count($identities)." identities\n";

foreach (array_slice($identities, 0, 10) as $identity) {
    echo "\nIdentity: ".$identity->getId()."\n";
    echo '  Name: '.$identity->getFirstName().' '.$identity->getLastName()."\n";
    echo '  Phone: '.$identity->getPhoneNumber()."\n";
    echo '  Type: '.$identity->getIdentityType()."\n";
    $country = $identity->country()->getIncluded();
    echo '  Country: '.($country ? $country->getName() : 'null')."\n";
    $birthCountry = $identity->birthCountry()->getIncluded();
    echo '  Birth Country: '.($birthCountry ? $birthCountry->getName() : 'null')."\n"; // 2026-04-16
    echo '  Birth Date: '.($identity->getBirthDate() ?? 'null')."\n";
    echo '  Verified: '.($identity->getVerified() ? 'true' : 'false')."\n";
    echo '  Created: '.$identity->getCreatedAt()."\n";
}

// List addresses
echo "\n=== Addresses ===\n";
$document = Address::all(['include' => 'identity']);
$addresses = $document->getData();
echo $foundPrefix.count($addresses)." addresses\n";

foreach (array_slice($addresses, 0, 10) as $address) {
    echo "\nAddress: ".$address->getId()."\n";
    echo '  Street: '.$address->getAddress()."\n";
    echo '  City: '.$address->getCityName()."\n";
    echo '  Postal Code: '.$address->getPostalCode()."\n";
    echo '  Verified: '.($address->getVerified() ? 'true' : 'false')."\n";
    $identity = $address->identity()->getIncluded();
    if ($identity) {
        echo '  Identity: '.$identity->getFirstName().' '.$identity->getLastName()."\n";
    }
}

// List proof types
echo "\n=== Proof Types ===\n";
$document = ProofType::all();
$proofTypes = $document->getData();
echo $foundPrefix.count($proofTypes)." proof types\n";

foreach (array_slice($proofTypes, 0, 10) as $pt) {
    echo $pt->getName().' ('.$pt->getEntityType().")\n";
}

echo "\n=== Identities and Proofs Workflow ===\n";
echo "1. Create an identity with personal information\n";
echo "2. Create an address linked to the identity\n";
echo "3. Encrypt and upload identity/address documents\n";
echo "4. Create proofs attached to identity/address with uploaded files\n";
echo "5. Monitor verification status\n";
echo "\nNote: Creating and uploading encrypted files requires additional SDK setup.\n";
