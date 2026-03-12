<?php

namespace Didww\Item\OrderItem;

abstract class AbstractReferencedDidOrderItem extends Did
{
    abstract protected function getReferencedIdKey(): string;

    protected function getCreatableAttributesKeys()
    {
        return $this->withOptionalKeys([$this->getReferencedIdKey(), 'sku_id']);
    }
}
