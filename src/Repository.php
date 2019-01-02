<?php

namespace Didww;
use Swis\JsonApi\Client\Exceptions\DocumentNotFoundException;
use Swis\JsonApi\Client\Interfaces\ItemDocumentInterface;
use Swis\JsonApi\Client\Exceptions\DocumentTypeException;

class Repository extends \Swis\JsonApi\Client\Repository
{
   public function setEndpoint($newEndpoint)
   {
     $this->endpoint = $newEndpoint;
   }

   #same as find but for singular resources (without id)
   public function take(array $parameters = [])
     {
         $document = $this->getClient()->get($this->getEndpoint().'?'.http_build_query($parameters));
         if (null === $document->getData()) {
            throw new DocumentNotFoundException();
         }
        if (!$document instanceof ItemDocumentInterface) {
            throw new DocumentTypeException(
                sprintf('Expected %s got %s', ItemDocumentInterface::class, get_class($document))
            );
        }

         return $document;
     }

}
