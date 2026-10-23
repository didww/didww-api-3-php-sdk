<?php

namespace Didww;

/**
 * The JSON:API client. Authentication is added by the Guzzle client it is
 * built with (see Configuration::configure()).
 */
class Client extends \Swis\JsonApi\Client\Client
{
    public static function sdkVersion(): string
    {
        if (class_exists(\Composer\InstalledVersions::class)) {
            return \Composer\InstalledVersions::getPrettyVersion('didww/didww-api-3-php-sdk') ?? 'unknown';
        }

        return 'unknown';
    }

    public function setUserAgent(string $userAgent)
    {
        $this->setDefaultHeaders($this->mergeHeaders(['User-Agent' => $userAgent]));
    }
}
