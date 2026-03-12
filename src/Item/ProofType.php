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

}
