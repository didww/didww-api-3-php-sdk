<?php

// Identity, address and proofs:
// 1. creates a personal identity in the US (2026-04-16 adds birth_country)
// 2. creates an address linked to the identity
// 3. finds a proof type for the identity and one for the address
// 4. encrypts sample.pdf with the DIDWW public keys and uploads it, once per
//    proof (one file per request)
// 5. creates a proof for the identity and one for the address
// 6. reads the verified flags of the identity and the address
// 7. deletes the address, the identity and the uploaded files, also when a
//    step fails
//
// Usage: DIDWW_API_KEY=your_api_key php examples/identity_address_proofs.php

require_once 'bootstrap.php';

use Didww\Encrypt;
use Didww\Enum\IdentityType;
use Didww\Item\Address;
use Didww\Item\Country;
use Didww\Item\EncryptedFile;
use Didww\Item\Identity;
use Didww\Item\Proof;
use Didww\Item\ProofType;
use Swis\JsonApi\Client\Collection;

// Stops the flow; the resources created so far are deleted.
final class FlowStopped extends RuntimeException
{
}

function printErrors(string $message, $document): void
{
    echo $message.":\n";
    foreach ($document->getErrors() as $error) {
        echo '  - '.($error->getDetail() ?? $error->getTitle())."\n";
    }
}

// Saves $item and returns the saved item; prints the errors and stops the
// flow when the API refuses it.
function saveOrFail($item, string $what, array $parameters = [])
{
    $document = $item->save($parameters);
    if ($document->hasErrors()) {
        printErrors("Error $what", $document);
        throw new FlowStopped("Stopped: error $what");
    }

    return $document->getData();
}

// Reports a failed delete, also a thrown one, so that the clean-up goes on.
function deleteOrReport($item, string $what): void
{
    try {
        $document = $item->delete();
    } catch (Throwable $e) {
        echo "Error deleting $what ".$item->getId().': '.$e->getMessage()."\n";

        return;
    }
    if ($document->hasErrors()) {
        printErrors("Error deleting $what ".$item->getId(), $document);
    } else {
        echo "  Deleted $what ".$item->getId()."\n";
    }
}

function findProofType(string $entityType): ProofType
{
    $proofTypes = ProofType::all(['filter' => ['entity_type' => $entityType]])->getData();
    if ($proofTypes->isEmpty()) {
        throw new FlowStopped("Stopped: no proof type for entity type $entityType");
    }

    return $proofTypes[0];
}

$countries = Country::all(['filter' => ['iso' => 'US']])->getData();
if ($countries->isEmpty()) {
    echo "Country US not found\n";
    exit(1);
}
$country = $countries[0];
echo 'Using country: '.$country->getName().' ('.$country->getId().")\n";

$identity = $address = null;
$fileIds = [];
$exitCode = 0;
try {
    echo "\n=== Creating Identity ===\n";
    $newIdentity = new Identity();
    $newIdentity->setIdentityType(IdentityType::PERSONAL);
    $newIdentity->setFirstName('John');
    $newIdentity->setLastName('Doe');
    $newIdentity->setPhoneNumber('12125551234');
    $newIdentity->setCountry(Country::build($country->getId()));
    $newIdentity->setBirthCountry(Country::build($country->getId())); // 2026-04-16
    $identity = saveOrFail($newIdentity, 'creating the identity', ['include' => 'country,birth_country']);
    echo 'Created identity: '.$identity->getId().' ('.$identity->getFirstName().' '.$identity->getLastName().")\n";
    $birthCountry = $identity->birthCountry()->getIncluded();
    echo '  Birth country: '.($birthCountry ? $birthCountry->getName() : 'null')."\n";

    echo "\n=== Creating Address ===\n";
    $newAddress = new Address();
    $newAddress->setCityName('New York');
    $newAddress->setPostalCode('10001');
    $newAddress->setAddress('123 Main St');
    $newAddress->setIdentity(Identity::build($identity->getId()));
    $newAddress->setCountry(Country::build($country->getId()));
    $address = saveOrFail($newAddress, 'creating the address');
    echo 'Created address: '.$address->getId().' ('.$address->getAddress().', '.$address->getCityName().")\n";

    echo "\n=== Proof Types ===\n";
    $proofTypes = [
        'identity' => findProofType(IdentityType::PERSONAL->value),
        'address' => findProofType('address'),
    ];
    foreach ($proofTypes as $entity => $proofType) {
        echo ucfirst($entity).' proof type: '.$proofType->getName()."\n";
    }

    echo "\n=== Encrypting and Uploading sample.pdf ===\n";
    $content = file_get_contents(__DIR__.'/sample.pdf');
    // Encrypt fetches the two DIDWW public keys
    $encrypt = new Encrypt();
    foreach (array_keys($proofTypes) as $entity) {
        $upload = EncryptedFile::upload($encrypt->getFingerprint(), $encrypt->encrypt($content), "$entity proof sample.pdf");
        if (!$upload->success()) {
            echo "Error uploading the $entity proof file (HTTP ".$upload->getResponseCode()."):\n";
            foreach ($upload->getErrors() as $error) {
                echo '  - '.($error['detail'] ?? $error['title'] ?? json_encode($error))."\n";
            }
            throw new FlowStopped('Stopped: the file upload failed');
        }
        $fileIds[$entity] = $upload->getId();
        echo "Uploaded the $entity proof file: ".$upload->getId()."\n";
    }

    echo "\n=== Creating Proofs ===\n";
    $entities = [
        'identity' => Identity::build($identity->getId()),
        'address' => Address::build($address->getId()),
    ];
    foreach ($fileIds as $entity => $fileId) {
        $proof = new Proof();
        $proof->setEntity($entities[$entity]);
        $proof->setProofType(ProofType::build($proofTypes[$entity]->getId()));
        $proof->setFiles(new Collection([EncryptedFile::build($fileId)]));
        $proof = saveOrFail($proof, "creating the $entity proof", ['include' => 'proof_type']);
        $proofType = $proof->proofType()->getIncluded();
        echo "Created $entity proof: ".$proof->getId().' (type '.($proofType ? $proofType->getName() : 'unknown').")\n";
    }

    echo "\n=== Verified Flags ===\n";
    // DIDWW reviews the proofs, so a new identity and address are not verified yet
    $reloadedIdentity = Identity::find($identity->getId())->getData();
    $reloadedAddress = Address::find($address->getId())->getData();
    echo 'Identity '.$reloadedIdentity->getId().': verified='.var_export($reloadedIdentity->getVerified(), true)."\n";
    echo 'Address '.$reloadedAddress->getId().': verified='.var_export($reloadedAddress->getVerified(), true)."\n";
} catch (FlowStopped $e) {
    echo $e->getMessage()."\n";
    $exitCode = 1;
} finally {
    echo "\n=== Cleaning Up ===\n";
    if (null !== $address) {
        deleteOrReport($address, 'address');
    }
    if (null !== $identity) {
        deleteOrReport($identity, 'identity');
    }
    foreach ($fileIds as $fileId) {
        deleteOrReport(EncryptedFile::build($fileId), 'encrypted file');
    }
}

exit($exitCode);
