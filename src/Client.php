<?php

namespace Didww;

class Client extends \Swis\JsonApi\Client\Client
{
    public function setApiKey(string $apiKey)
    {
        $this->setDefaultHeaders($this->mergeHeaders(['api-key' => $apiKey]));
    }
}
