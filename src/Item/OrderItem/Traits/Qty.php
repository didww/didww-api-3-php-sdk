<?php

namespace Didww\Item\OrderItem\Traits;

trait Qty
{
    public function setQty(int $qty)
    {
        $this->attributes['qty'] = $qty;
    }

    public function getQty()
    {
        return $this->attributes['qty'];
    }
}
