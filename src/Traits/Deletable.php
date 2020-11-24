<?php

namespace Didww\Traits;

trait Deletable
{
    public function delete()
    {
        return self::getRepository()->delete($this->id);
    }
}
