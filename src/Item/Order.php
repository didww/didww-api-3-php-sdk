<?php

namespace Didww\Item;

class Order extends BaseItem
{
    use \Didww\Traits\Fetchable;
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Deletable;

    protected $type = 'orders';

    public static function itemFactory(string $type, $attributes)
    {
        $class = '\\Didww\\Item\\OrderItem\\'.ucfirst($type);

        return new $class($attributes);
    }

    public function toJsonApiArray(): array
    {
        $data = parent::toJsonApiArray();

        $itemData = [];
        if (isset($data['attributes']['items'])) {
            foreach ($data['attributes']['items'] as $item) {
                array_push($itemData, $item->toJsonApiArray());
            }
        }

        $data['attributes']['items'] = $itemData;

        return $data;
    }

    protected function getWhiteListAttributesKeys(): array
    {
        return [
         'allow_back_ordering',
         'items',
         'callback_url',
         'callback_method',
       ];
    }

    public function fill(array $attributes)
    {
        if (isset($attributes['items'])) {
            $this->fillItems($attributes['items']);
            unset($attributes['items']);
        }

        return parent::fill($attributes);
    }

    private function fillItems($items)
    {
        if (is_array($items)) {
            $itemsCollection = [];
            foreach ($items as $value) {
                if ($value instanceof \Didww\Item\OrderItem\Base) {
                    $itemsCollection[] = $value;
                } elseif (is_object($value)) {
                    $itemsCollection[] = $this->buildItem($value->type, $value->attributes);
                }
            }
            $this->items = $itemsCollection;
        } else {
            throw new \InvalidArgumentException('can\'t set items');
        }
    }

    private function buildItem($type, $attributes)
    {
        $parts = explode('_order_items', $type);

        return self::itemFactory($parts[0], (array) $attributes);
    }

    public function getAmount(): float
    {
        return (float) $this->getAttributes()['amount'];
    }

    public function getStatus(): string
    {
        return $this->getAttributes()['status'];
    }

    public function getCreatedAt(): \DateTime
    {
        return new \DateTime($this->getAttributes()['created_at']);
    }

    public function getDescription(): string
    {
        return $this->getAttributes()['description'];
    }

    public function getReference(): string
    {
        return $this->getAttributes()['reference'];
    }

    public function getItems(): array
    {
        return $this->getAttributes()['items'];
    }

    public function setItems(array $items)
    {
        $this->attributes['items'] = $items;
    }

    public function getCallbackUrl(): ?string
    {
        return $this->attributes['callback_url'];
    }

    public function setCallbackUrl(string $callbackUrl)
    {
        $this->attributes['callback_url'] = $callbackUrl;
    }

    public function getCallbackMethod(): ?string
    {
        return $this->attributes['callback_method'];
    }

    public function setCallbackMethod(string $callbackMethod)
    {
        $this->attributes['callback_method'] = $callbackMethod;
    }
}
