<?php

require_once 'vendor/autoload.php';

$apiKey = $_SERVER['DIDWW_API_KEY'] ?? exit("Please provide an DIDWW API key!\n");

$credentials = new Didww\Credentials($apiKey, 'sandbox');
$httpClientConfig = [
    'timeout' => 20,
    // 'debug' => true,
    // 'sink' => STDOUT,
];
Didww\Configuration::configure($credentials, $httpClientConfig);
