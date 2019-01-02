<?php

namespace Didww;

class Client extends \Swis\JsonApi\Client\Client
{
    public function setApiKey(string $apiKey)
    {
        $this->defaultHeaders = $this->mergeHeaders(['api-key' => $apiKey]);
    }
}
