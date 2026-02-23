<?php

require_once 'bootstrap.php';

// FILE_PATH="/path/to/file.jpeg" DIDWW_API_KEY=PLACEYOURAPIKEYHERE php examples/upload_file.php
$filePath = $_SERVER['FILE_PATH'] ?? exit("Please provide FILE_PATH env variable\n");
$fileContent = file_get_contents($filePath);
$enc = new Didww\Encrypt();
$fingerprint = $enc->getFingerprint();
$encData = $enc->encrypt($fileContent);
$uploadResult = Didww\Item\EncryptedFile::upload($fingerprint, [$encData], ['php library example']);
var_dump(
    $uploadResult->success(), // bool(true)
    $uploadResult->getIds() // array(1) { [0]=> string(36) "659fcd19-063d-4471-9a73-33f3f9e2bab7" }
);
