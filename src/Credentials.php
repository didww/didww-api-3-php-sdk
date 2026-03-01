<?php

namespace Didww;

class Credentials
{
    private $apiKey;
    private $env;
    private $version;

    public function getEnv()
    {
        return $this->env;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function __construct($apiKey, $env, ?string $version = '2022-05-10')
    {
        $this->apiKey = $apiKey;
        $this->env = $env;
        $this->version = $version;
    }

    public function getEndpoint(): string
    {
        if ('production' == $this->getEnv()) {
            return 'https://api.didww.com/v3';
        }

        return 'https://sandbox-api.didww.com/v3';
    }
}
