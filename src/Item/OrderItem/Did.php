<?php

namespace Didww\Item\OrderItem;

class Did extends Base
{
    use Traits\Sku;
    use Traits\Qty;

    protected const OPTIONAL_KEYS = ['billing_cycles_count', 'nanpa_prefix_id'];

    protected function getCreatableAttributesKeys()
    {
        return $this->withOptionalKeys(['sku_id', 'qty']);
    }

    protected function withOptionalKeys($attributes = [])
    {
        foreach (self::OPTIONAL_KEYS as $key) {
            if (array_key_exists($key, $this->getAttributes())) {
                array_push($attributes, $key);
            }
        }

        return $attributes;
    }

    public function getType(): string
    {
        return 'did_order_items';
    }

    public function getDidGroupId()
    {
        return $this->getAttributes()['did_group_id'];
    }

    public function setBillingCyclesCount(?int $billingCyclesCount)
    {
        $this->attributes['billing_cycles_count'] = $billingCyclesCount;
    }

    public function getBillingCyclesCount(): ?int
    {
        return $this->attributes['billing_cycles_count'];
    }
}
