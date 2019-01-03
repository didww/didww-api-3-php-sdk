<?php

require_once 'bootstrap.php';

// get last ordered DID
$did = \Didww\Item\Did::all(['sort' => '-created_at', 'page' => ['size' => 1, 'number' => 1]])->getData()[0];

//see trunks example to create Trunk
//get last SIP trunk
$trunk = \Didww\Item\Trunk::all(['sort' => '-created_at', 'filter' => ['configuration.type' => 'sip_configurations']])->getData()[0];

$did->setTrunk($trunk);
$didDocument = $did->save(['include' => 'trunk']);

if ($didDocument->hasErrors()){
  var_dump($didDocument->getErrors());
}else{
  $trunk = $didDocument->getData()->trunk()->getIncluded();
  var_dump(
      $trunk->getId(), // 1f6fc2bd-f081-4202-9b1a-d9cb88d942b9
      $trunk->getName(), // "My New Custom Sip Trunk 5c2e393794b07"
      $trunk->getCreatedAt(), // object(DateTime)
      $trunk->getRingingTimeout() // int(30)
  );
}

?>
