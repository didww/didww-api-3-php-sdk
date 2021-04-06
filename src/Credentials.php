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

    public function __construct($apiKey, $env, ?string $version = null)
    {
        $this->apiKey = $apiKey;
        $this->env = $env;
        $this->version = $version;
    }

    public function getEndpoint(): string
    {
        if ('production' == $this->getEnv()) {
            return 'https://api.didww.com/v3';
        } else {
            return 'https://sandbox-api.didww.com/v3';
        }
    }
}
