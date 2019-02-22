<?php

namespace Didww;

class Repository extends \Swis\JsonApi\Client\Repository
{
    public function setEndpoint($newEndpoint)
    {
        $this->endpoint = $newEndpoint;
    }
}
