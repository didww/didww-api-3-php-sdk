<?php

namespace Didww;

class Client extends \Swis\JsonApi\Client\Client
{
    private const PUBLIC_KEYS_ENDPOINT = '/public_keys';

    protected function buildRequest(string $method, string $endpoint, $body = null, array $headers = []): \Psr\Http\Message\RequestInterface
    {
        $request = parent::buildRequest($method, $endpoint, $body, $headers);

        if (self::PUBLIC_KEYS_ENDPOINT === $endpoint) {
            $request = $request->withoutHeader('api-key');
        }

        return $request;
    }

    public static function sdkVersion(): string
    {
        if (class_exists(\Composer\InstalledVersions::class)) {
            return \Composer\InstalledVersions::getPrettyVersion('didww/didww-api-3-php-sdk') ?? 'unknown';
        }

        return 'unknown';
    }

    public function setApiKey(string $apiKey)
    {
        $this->setDefaultHeaders($this->mergeHeaders(['api-key' => $apiKey]));
    }

    public function setVersion(string $version)
    {
        $this->setDefaultHeaders($this->mergeHeaders(['x-didww-api-version' => $version]));
    }

    public function setUserAgent(string $userAgent)
    {
        $this->setDefaultHeaders($this->mergeHeaders(['User-Agent' => $userAgent]));
    }
}
