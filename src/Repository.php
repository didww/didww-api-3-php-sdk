<?php

namespace Didww;

class Repository extends \Swis\JsonApi\Client\Repository
{
    public function setEndpoint($newEndpoint)
    {
        $this->endpoint = $newEndpoint;
    }

    //same as find but for singular resources (without id)
    public function take(array $parameters = [])
    {
        return $this->getClient()->get($this->getEndpoint().'?'.http_build_query($parameters));
    }
}
