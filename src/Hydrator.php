<?php

namespace Didww;

use Art4\JsonApiClient\ResourceIdentifierCollectionInterface;
use Art4\JsonApiClient\ResourceIdentifierInterface;
use Art4\JsonApiClient\ResourceItemInterface;
use Swis\JsonApi\Client\Collection;
use Swis\JsonApi\Client\Interfaces\ItemInterface;

class Hydrator extends \Swis\JsonApi\Client\JsonApi\Hydrator
{
    /**
     * @param \Swis\JsonApi\Client\Collection $jsonApiItems
     * @param \Swis\JsonApi\Client\Collection $items
     */
    public function hydrateRelationships(Collection $jsonApiItems, Collection $items)
    {
        $keyedItems = $items->reverse()->keyBy(
      function (ItemInterface $item) {
          return $this->getItemKey($item);
      }
    );

        $jsonApiItems->each(
      function (ResourceItemInterface $jsonApiItem) use ($keyedItems) {
          if (!$jsonApiItem->has('relationships')) {
              return;
          }

          $item = $this->getItem($keyedItems, $jsonApiItem);

          if (null === $item) {
              return;
          }

          foreach ($jsonApiItem->get('relationships')->asArray() as $name => $relationship) {
              // patched here
              if (!$relationship->has('data')) {
                  continue;
              }
              // patched here

              /** @var \Art4\JsonApiClient\ElementInterface $data */
              $data = $relationship->get('data');
              $method = camel_case($name);

              if ($data instanceof ResourceIdentifierInterface) {
                  $includedItem = $this->getItem($keyedItems, $data);

                  if (null === $includedItem) {
                      continue;
                  }

                  $item->setRelation($method, $includedItem);
              } elseif ($data instanceof ResourceIdentifierCollectionInterface) {
                  $collection = $this->getCollection($keyedItems, $data);

                  $item->setRelation($method, $collection);
              }
          }
      }
    );
    }
}
