<?php

namespace Didww;

class Configuration
{
    private static $documentClient;

    private static $credentials;

    public static function getCredentials()
    {
        return self::$credentials;
    }

    public static function getDocumentClient()
    {
        return self::$documentClient;
    }

    public static function configure(Credentials $credentials, array $httpClientConfig = [])
    {
        self::$credentials = $credentials;

        $httpClient = new \GuzzleHttp\Client($httpClientConfig);

        $client = new Client($httpClient);
        $client->setBaseUri($credentials->getEndpoint());
        $client->setApiKey($credentials->getApiKey());
        $version = $credentials->getVersion();
        if (null != $version) {
            $client->setVersion($version);
        }

        $typeMapper = new TypeMapper();
        $typeMapper->registerItems();

        $metaParser = new \Swis\JsonApi\Client\Parsers\MetaParser();
        $linksParser = new \Swis\JsonApi\Client\Parsers\LinksParser($metaParser);
        $itemParser = new \Swis\JsonApi\Client\Parsers\ItemParser($typeMapper, $linksParser, $metaParser);
        $collectionParser = new \Swis\JsonApi\Client\Parsers\CollectionParser($itemParser);
        $errorParser = new \Swis\JsonApi\Client\Parsers\ErrorParser($linksParser, $metaParser);
        $errorCollectionParser = new \Swis\JsonApi\Client\Parsers\ErrorCollectionParser($errorParser);
        $jsonapiParser = new \Swis\JsonApi\Client\Parsers\JsonapiParser($metaParser);
        $documentParser = new \Swis\JsonApi\Client\Parsers\DocumentParser(
            $itemParser,
            $collectionParser,
            $errorCollectionParser,
            $linksParser,
            $jsonapiParser,
            $metaParser
        );
        $responseParser = new \Swis\JsonApi\Client\Parsers\ResponseParser($documentParser);

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
