<?php

namespace Didww;

class Configuration
{
    private static $documentClient;

    private static $credentials;

    private static $httpClient;

    public static function getCredentials()
    {
        return self::$credentials;
    }

    public static function getDocumentClient()
    {
        return self::$documentClient;
    }

    public static function getHttpClient()
    {
        return self::$httpClient;
    }

    public static function configure(Credentials $credentials, array $httpClientConfig = [])
    {
        self::$credentials = $credentials;

        $httpClient = new \GuzzleHttp\Client($httpClientConfig);
        self::$httpClient = $httpClient;

        $client = new Client($httpClient);
        $client->setBaseUri($credentials->getEndpoint());
        $client->setApiKey($credentials->getApiKey());
        $client->setUserAgent('didww-php-sdk/'.Client::sdkVersion());
        $version = $credentials->getVersion();
        if (null !== $version) {
            $client->setVersion($version);
        }

        $typeMapper = new TypeMapper();
        $typeMapper->registerItems();

        $responseParser = \Swis\JsonApi\Client\Parsers\ResponseParser::create($typeMapper);

        self::$documentClient = new \Swis\JsonApi\Client\DocumentClient($client, $responseParser);
    }

    public static function getApiKey(): string
    {
        return self::$credentials->getApiKey();
    }

    public static function getBaseUri(): string
    {
        return self::getDocumentClient()->getBaseUri();
    }
}
