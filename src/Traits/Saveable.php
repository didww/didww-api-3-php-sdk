<?php

namespace Didww\Traits;

trait Saveable
{
    public function save(array $parameters = [])
    {
        return self::getRepository()->save($this, $parameters);
    }
}
