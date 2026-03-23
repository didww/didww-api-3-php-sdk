<?php

namespace Didww\Item;

use Didww\Enum\CallbackMethod;
use Didww\Enum\OrderStatus;
use Didww\Item\OrderItem\Capacity;
use Didww\Item\OrderItem\Did as DidOrderItem;
use Didww\Item\OrderItem\Generic;
use Didww\Traits\Deletable;
use Didww\Traits\Fetchable;
use Didww\Traits\Saveable;

class Order extends BaseItem
{
    use Fetchable;
    use Saveable;
    use Deletable;

    protected $type = 'orders';

    private const ITEM_CLASSES = [
        'did' => DidOrderItem::class,
        'capacity' => Capacity::class,
        'generic' => Generic::class,
    ];

    public static function itemFactory(string $type, $attributes)
    {
        $class = self::ITEM_CLASSES[$type] ?? throw new \InvalidArgumentException("Unknown order item type: $type");

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
                if ($value instanceof OrderItem\Base) {
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

    public function getStatus(): OrderStatus
    {
        return $this->enumAttribute('status', OrderStatus::class);
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->dateAttribute('created_at');
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
        return $this->attribute('callback_url');
    }

    public function setCallbackUrl(string $callbackUrl)
    {
        $this->attributes['callback_url'] = $callbackUrl;
    }

    public function getCallbackMethod(): ?CallbackMethod
    {
        return $this->enumAttribute('callback_method', CallbackMethod::class);
    }

    public function setCallbackMethod(CallbackMethod|string $callbackMethod)
    {
        $this->setEnumAttribute('callback_method', $callbackMethod);
    }
}
