<?php

require_once __DIR__.'/../vendor/autoload.php';
// We want to compare all headers except for the User-Agent because it's based
// on the local php and curl versions which can change, and Accept-Encoding,
// which curl adds or leaves out depending on the decode_content option.
VCR\VCR::configure()->addRequestMatcher('custom_headers', function (VCR\Request $first, VCR\Request $second) {
    $firstHeaders = $first->getHeaders();
    $secondHeaders = $second->getHeaders();
    unset($firstHeaders['User-Agent'], $firstHeaders['Accept-Encoding']);
    unset($secondHeaders['User-Agent'], $secondHeaders['Accept-Encoding']);

    return 0 === count(array_diff_assoc($firstHeaders, $secondHeaders));
});
// Configure how live requests are compared against recorded tests and
// determined to be the same.
VCR\VCR::configure()->enableRequestMatchers(['method', 'url', 'query_string', 'body', 'custom_headers']);
// Tests never go to the network: a request without a recording fails.
VCR\VCR::configure()->setMode('none');
// This instruct PHP-VCR to only instrument guzzle with code to intercept requests
// \VCR\VCR::configure()->setWhiteList(array('vendor/guzzlehttp'));
VCR\VCR::turnOn();
