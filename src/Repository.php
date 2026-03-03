<?php

namespace Didww;

use Swis\JsonApi\Client\Interfaces\ItemInterface;

class Repository extends \Swis\JsonApi\Client\Repository
{
    public function setEndpoint($newEndpoint)
    {
        $this->endpoint = $newEndpoint;
    }

    public function save(ItemInterface $item, array $parameters = [], array $headers = [])
    {
        if ($item->isNew()) {
            return $this->saveNew($item, $parameters, $headers);
        }

        return $this->updateWithDirtyTracking($item, $parameters, $headers);
    }

    private function updateWithDirtyTracking(ItemInterface $item, array $parameters, array $headers)
    {
        $document = new PatchItemDocument();
        $document->setData($item);
        $document->setIncluded($this->documentFactory->make($item)->getIncluded());

        $uri = $this->getEndpoint().'/'.rawurlencode($item->getId());
        $queryString = http_build_query($parameters);
        if ('' !== $queryString) {
            $uri .= '?'.$queryString;
        }

        return $this->getClient()->patch(
            $uri,
            $document,
            $headers,
        );
    }
}
