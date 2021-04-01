<?php

namespace Didww;

class Credentials
{
    private $apiKey;
    private $env;

    public function getEnv()
    {
        return $this->env;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function __construct($apiKey, $env)
    {
        $this->apiKey = $apiKey;
        $this->env = $env;
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
