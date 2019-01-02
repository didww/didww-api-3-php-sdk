<?php

namespace Didww\Traits;

trait Saveable
{
    public function save(array $parameters = [])
    {
        $itemDocument = new \Swis\JsonApi\Client\ItemDocument();
        $itemDocument->setData($this);

        return self::getRepository()->save($itemDocument, $parameters);
    }
}
