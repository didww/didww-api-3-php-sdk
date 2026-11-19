<?php

// Encrypts a file with the DIDWW public keys and uploads it to
// encrypted_files. The returned ID can be attached to a proof (see
// identity_address_proofs.php).
//
// Usage: DIDWW_API_KEY=your_api_key [FILE_PATH=/path/to/file.pdf] php examples/upload_file.php
// Without FILE_PATH the script uploads sample.pdf from this directory.

require_once 'bootstrap.php';

$filePath = exampleEnv('FILE_PATH') ?? __DIR__.'/sample.pdf';
$fileContent = file_get_contents($filePath);
if (false === $fileContent) {
    echo "Cannot read $filePath\n";
    exit(1);
}
$enc = new Didww\Encrypt();
$fingerprint = $enc->getFingerprint();
$encData = $enc->encrypt($fileContent);
$uploadResult = Didww\Item\EncryptedFile::upload($fingerprint, $encData, basename($filePath));
var_dump(
    $uploadResult->success(), // bool(true)
    $uploadResult->getId() // string(36) "659fcd19-063d-4471-9a73-33f3f9e2bab7"
);
