<?php

namespace Didww\Item\OrderItem;

abstract class Base implements \Swis\JsonApi\Client\Interfaces\DataInterface
{
    protected $attributes = [];

    abstract protected function getType();

    abstract protected function getCreatableAttributesKeys();

    public function __construct($attributes = [])
    {
        $this->fill($attributes);
    }

    public function toJsonApiArray(): array
    {
        return [
          'type' => $this->getType(),
          'attributes' => $this->getCreatableAttributes(),
      ];
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getCreatableAttributes(): array
    {
        $creatableAttributes = [];
        foreach ($this->getCreatableAttributesKeys() as $key) {
            $creatableAttributes[$key] = $this->getAttributes()[$key];
        }

        return $creatableAttributes;
    }

    public function fill(array $attributes)
    {
        //remove deprecated attributes
        unset($attributes['monthly_price']);
        unset($attributes['setup_price']);
        $this->attributes = $attributes;
    }

    public function getQty(): int
    {
        return $this->getAttributes()['qty'];
    }

    public function getNrc(): float
    {
        return (float)$this->getAttributes()['nrc'];
    }

    public function getMrc(): float
    {
        return (float)$this->getAttributes()['mrc'];
    }

    public function getBilledFrom()
    {
        return $this->getAttributes()['billed_from'];
    }

    public function getBilledTo()
    {
        return $this->getAttributes()['billed_to'];
    }

    public function getProratedMrc(): bool
    {
        return (bool)$this->getAttributes()['prorated_mrc'];
    }
}
