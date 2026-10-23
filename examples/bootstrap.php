<?php

// Configures the SDK for the examples from environment variables:
//   DIDWW_API_KEY (required)  your DIDWW API key
//   DIDWW_ENV                 "sandbox" (default) or "production"
//   DIDWW_API_URL, DIDWW_AUTH_URL, DIDWW_API_VIN_URL, DIDWW_API_VOUT_URL,
//   DIDWW_API_SMS_URL         optional URL overrides of the JSON:API, the token
//                             service and the Voice IN, Voice OUT and SMS
//                             prices services; the environment's by default

require_once __DIR__.'/../vendor/autoload.php';

function exampleEnv(string $name): ?string
{
    $value = getenv($name);

    return false === $value || '' === $value ? null : $value;
}

$apiKey = exampleEnv('DIDWW_API_KEY') ?? exit("Please provide an DIDWW API key!\n");

$credentials = new Didww\Credentials($apiKey, exampleEnv('DIDWW_ENV') ?? 'sandbox');

// Guzzle options on top of the SDK defaults (connect_timeout 10 s,
// read_timeout 60 s, no redirects). A "timeout" bounds the whole transfer,
// file downloads included.
$httpClientConfig = [
    // 'debug' => true,
];

Didww\Configuration::configure(
    $credentials,
    $httpClientConfig,
    baseUrl: exampleEnv('DIDWW_API_URL'),
    authUrl: exampleEnv('DIDWW_AUTH_URL'),
    voiceInPricesUrl: exampleEnv('DIDWW_API_VIN_URL'),
    voiceOutPricesUrl: exampleEnv('DIDWW_API_VOUT_URL'),
    smsPricesUrl: exampleEnv('DIDWW_API_SMS_URL'),
);
