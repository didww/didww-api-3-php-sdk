<?php

require_once 'bootstrap.php';

function build_data_files($boundary, $fields, $files) {
    $data = '';
    $eol = "\r\n";

    $delimiter = '-------------' . $boundary;

    foreach ($fields as $name => $content) {
        $data .= "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="' . $name . "\"" . $eol . $eol
            . $content . $eol;
    }

    foreach ($files as $file) {
        $data .= "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="' . $file['name'] . '"; filename="' . $file['filename'] . '"' . $eol
            . 'Content-Type: ' . $file['content-type'] . $eol
            . 'Content-Transfer-Encoding: binary' . $eol;

        $data .= $eol;
        $data .= $file['data'] . $eol;
    }
    $data .= "--" . $delimiter . "--" . $eol;

    return $data;
}

function encrypt_rsa_oaep($public_key, $text) {
    $rsa = \phpseclib3\Crypt\PublicKeyLoader::load($public_key)
        ->withHash('sha256')
        ->withMGFHash('sha256');

    return $rsa->encrypt($text);
}

function fetchPublicKeys() {
    global $apiHost;

    $url = $apiHost . '/v3/public_keys';

    // curl
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Accept: application/vnd.api+json"
        ),
    ));


    $response = curl_exec($curl);
    curl_close($curl);

    return array_map(function($el) {
        return $el->attributes->key;
    }, json_decode($response)->data);
}

function getPublicKeyA() {
    global $publicKeys;

    return $publicKeys[0];
}

function getPublicKeyB() {
    global $publicKeys;

    return $publicKeys[1];
}

function encrypt($binary) {
    $aes_key = openssl_random_pseudo_bytes(32);
    $aes_iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

    $encrypted_aes = openssl_encrypt(base64_encode($binary), 'aes-256-cbc', $aes_key, 0, $aes_iv);

    $aes_credentials = bin2hex($aes_key) . ':::' . bin2hex($aes_iv);
    $encrypted_rsa_a = encrypt_rsa_oaep(getPublicKeyA(), $aes_credentials);
    $encrypted_rsa_b = encrypt_rsa_oaep(getPublicKeyB(), $aes_credentials);

    return base64_encode($encrypted_rsa_a) . ':::' . base64_encode($encrypted_rsa_b) . ':::' . base64_encode($encrypted_aes);
}

function getFingerprint() {
    return getFingerprintFor(getPublicKeyA()) . ':::' . getFingerprintFor(getPublicKeyB());
}

function normalizePublicKey($public_key) {
    if ($public_key[-1] != "\n") {
        $public_key .= "\n";
    }
    $public_key_array = explode("\n", $public_key);

    return implode('', array_slice($public_key_array, 1, count($public_key_array) - 3));
}

function getFingerprintFor($public_key) {
    $public_key_base64 = normalizePublicKey($public_key);
    $public_key_bin = base64_decode($public_key_base64);
    $digest = sha1($public_key_bin, true);

    return bin2hex($digest);
}

function upload_files($fields, $files) {
    global $apiKey, $apiHost;

    // URL to upload to
    $url = $apiHost . '/v3/encrypted_files';

    // curl
    $curl = curl_init();

    $boundary = uniqid();
    $delimiter = '-------------' . $boundary;

    $post_data = build_data_files($boundary, $fields, $files);

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_HTTPHEADER => array(
            "Api-Key: $apiKey",
            "Content-Type: multipart/form-data; boundary=" . $delimiter,
            "Content-Length: " . strlen($post_data),
            "Accept: application/json"
        ),
    ));


    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
}

$apiHost = 'https://staging-api.didww.com';
$filePath = '/Users/igor.gonchar/Desktop/didww.jpeg';
$publicKeys = fetchPublicKeys();

// data fields for POST request
$fields = array(
    "encrypted_files[encryption_fingerprint]" => getFingerprint(),
    "encrypted_files[items][][description]" => "PHP SDK upload"
);

// files to upload
$filenames = array($filePath);

$files = array();
foreach ($filenames as $f){
    $data = encrypt(file_get_contents($f));
    file_put_contents('/Users/igor.gonchar/Desktop/didww.enc', $data);
    $files[] = [
        'filename' => 'didww.jpeg',
        'name' => 'encrypted_files[items][][file]',
        'content-type' => 'image/jpeg',
        'data' => $data
    ];
}

$response = upload_files($fields, $files);
var_dump($response, getFingerprint(), getPublicKeyA(), getPublicKeyB());
