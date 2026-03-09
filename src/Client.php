<?php

namespace Didww;

class Client extends \Swis\JsonApi\Client\Client
{
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
