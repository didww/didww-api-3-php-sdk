<?php

namespace Didww\Traits;

trait Deletable
{
    public function delete()
    {
        return self::getRepository()->deleteById($this->id);
    }
}
