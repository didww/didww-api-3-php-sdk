<?php

namespace Didww\Item\OrderItem;

abstract class AbstractReferencedDidOrderItem extends Did
{
    use Traits\Sku;

    abstract protected function getReferencedIdKey(): string;

    protected function getCreatableAttributesKeys(): array
    {
        return $this->withOptionalKeys([$this->getReferencedIdKey(), 'sku_id']);
    }
}
