<?php

namespace Didww\Item;

use Didww\Traits\Fetchable;

class ProofType extends BaseItem
{
    use Fetchable;

    public static function getEndpoint(): string
    {
        return '/proof_types';
    }

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
