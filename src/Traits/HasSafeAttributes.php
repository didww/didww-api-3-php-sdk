<?php

namespace Didww\Traits;

trait HasSafeAttributes
{
    protected function attribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }
}
