<?php

namespace Didww;

use Swis\JsonApi\Client\ItemDocument;

class PatchItemDocument extends ItemDocument
{
    public function toArray(): array
    {
        $document = [];

        $item = $this->getData();
        if ($item instanceof Item\BaseItem) {
            $document['data'] = $item->toJsonApiPatchArray();
        } else {
            $document['data'] = $item->toJsonApiArray();
        }

        if ($this->getIncluded()->isNotEmpty()) {
            $document['included'] = $this->getIncluded()->toJsonApiArray();
        }

        return $document;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return (object) $this->toArray();
    }
}
