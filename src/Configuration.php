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
        $httpClient = \Http\Adapter\Guzzle6\Client::createWithConfig($httpClientConfig);

        $messageFactory = \Http\Discovery\MessageFactoryDiscovery::find();

        self::$credentials = $credentials;
        $client = new Client($httpClient, self::$credentials->getEndpoint(), $messageFactory);
        $client->setApiKey(self::$credentials->getApiKey());

        $typeMapper = new TypeMapper();
        $typeMapper->registerItems();
        $linksParser = new \Swis\JsonApi\Client\JsonApi\LinksParser();
        $parser = new \Swis\JsonApi\Client\JsonApi\Parser(
            new \Art4\JsonApiClient\Utils\Manager(),
            new \Swis\JsonApi\Client\JsonApi\Hydrator($typeMapper, $linksParser),
            new \Swis\JsonApi\Client\JsonApi\ErrorsParser($linksParser),
            $linksParser
        );

        self::$documentClient = new \Swis\JsonApi\Client\DocumentClient($client, $parser);
    }
}
