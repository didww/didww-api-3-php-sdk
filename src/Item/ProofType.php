<?php

namespace Didww\Item;

class ProofType extends BaseItem
{
    use \Didww\Traits\Fetchable;

    protected $type = 'proof_types';

    public function getName(): string
    {
        return $this->attributes['name'];
    }

    public function getEntityType(): string
    {
        return $this->attributes['entity_type'];
    }

    /** @return array [
     * ]
     * 'name' => string
     * 'entity_type' => string // 'Personal' or 'Business' for identity entity, 'Address' for address entity
     */
    public function getAttributes(): array
    {
        return parent::getAttributes();
    }
}
