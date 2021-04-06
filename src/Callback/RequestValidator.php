<?php

namespace Didww\Callback;

class RequestValidator
{
    private static $headerName = 'X-DIDWW-Signature';
    private $apiKey;

    public static function getHeaderName(): string
    {
        return RequestValidator::$headerName;
    }

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $url       full original URL from request
     * @param array  $payload   full payload
     * @param string $signature signature header value
     *
     * @return bool whether signature valid or not
     */
    public function validate(string $url, array $payload, string $signature): bool
    {
        if (null == $signature || 0 == strlen($signature)) {
            return false;
        }

        return $this->validSignature($url, $payload) == $signature;
    }

    private function validSignature(string $url, array $payload): string
    {
        $sortedPayload = $payload;
        asort($sortedPayload);
        $data = $this->normalizeUrl($url);
        foreach ($sortedPayload as $key => $value) {
            $data .= $key.$value;
        }

        return hash_hmac('sha1', $data, $this->apiKey);
    }

    private function normalizeUrl(string $url): string
    {
        $parsedUrl = parse_url($url);
        if (false == $parsedUrl) {
            return '';
        }

        $scheme = $parsedUrl['scheme'];

        if (array_key_exists('user', $parsedUrl) && array_key_exists('password', $parsedUrl)) {
            $userInfo = $parsedUrl['user'].':'.$parsedUrl['password'].'@';
        } elseif (array_key_exists('user', $parsedUrl)) {
            $userInfo = $parsedUrl['user'].'@';
        } else {
            $userInfo = '';
        }

        $host = $parsedUrl['host'];


        if (array_key_exists('port', $parsedUrl)) {
            $port = $parsedUrl['port'];
        } elseif ('https' == $scheme) {
            $port = 443;
        } else {
            $port = 80;
        }

        if (array_key_exists('path', $parsedUrl)) {
            $path = $parsedUrl['path'];
        } else {
            $path = '';
        }

        if (array_key_exists('query', $parsedUrl)) {
            $query = '?'.$parsedUrl['query'];
        } else {
            $query = '';
        }

        if (array_key_exists('fragment', $parsedUrl)) {
            $fragment = '#'.$parsedUrl['fragment'];
        } else {
            $fragment = '';
        }

        return $scheme.'://'.$userInfo.$host.':'.$port.$path.$query.$fragment;
    }
}
