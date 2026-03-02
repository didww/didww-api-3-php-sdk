<?php

namespace Didww\Traits;

use Didww\Item\BaseItem;

trait Saveable
{
    public function save(array $parameters = [])
    {
        $document = self::getRepository()->save($this, $parameters);
        $data = $document->getData();
        if ($data instanceof BaseItem) {
            $data->syncPersistedState();
        }

        return $document;
    }
}
