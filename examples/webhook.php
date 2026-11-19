<?php

// Validates the signature of a DIDWW callback with
// Didww\Callback\RequestValidator. The script builds a sample order callback
// (URL and payload), signs it the way DIDWW signs callbacks, and validates it:
// the signed payload is valid, a tampered payload is not.
//
// DIDWW signs a callback with HMAC-SHA1, keyed with the callback secret
// enabled in the DIDWW User Panel, over the callback URL followed by the
// payload keys and values sorted by key. The URL is signed with its port
// spelled out (443 for https, 80 for http). The signature is sent in the
// X-DIDWW-Signature header.
//
// No API call is made, so no API key is needed.
//
// Usage: php examples/webhook.php

require_once __DIR__.'/../vendor/autoload.php';

use Didww\Callback\RequestValidator;

// The data DIDWW signs: the URL with its port, then every key and value of
// the payload, sorted by key.
function signCallback(string $secret, string $url, array $payload): string
{
    $parts = parse_url($url);
    $port = $parts['port'] ?? ('https' === $parts['scheme'] ? 443 : 80);
    $data = $parts['scheme'].'://'.$parts['host'].':'.$port.($parts['path'] ?? '');
    if (isset($parts['query'])) {
        $data .= '?'.$parts['query'];
    }
    ksort($payload);
    foreach ($payload as $key => $value) {
        $data .= $key.$value;
    }

    return hash_hmac('sha1', $data, $secret);
}

$callbackSecret = 'your-callback-secret';
$validator = new RequestValidator($callbackSecret);

// The full URL the callback was sent to, and its payload
$url = 'https://example.com/callbacks/orders?source=didww';
$payload = [
    'id' => '2989925f-7df3-4dc9-9944-d1fa0f4e8e86',
    'type' => 'orders',
    'status' => 'completed',
    'created_at' => '2026-11-19T10:00:00.000Z',
];
// The value of the X-DIDWW-Signature header
$signature = signCallback($callbackSecret, $url, $payload);

echo "=== Signed callback ===\n";
echo RequestValidator::getHeaderName().': '.$signature."\n";
echo 'valid: '.var_export($validator->validate($url, $payload, $signature), true)."\n";

echo "\n=== Tampered payload (status changed) ===\n";
$tampered = $payload;
$tampered['status'] = 'canceled';
echo 'valid: '.var_export($validator->validate($url, $tampered, $signature), true)."\n";
