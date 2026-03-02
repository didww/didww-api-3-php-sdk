<?php

namespace Didww\Traits;

use Didww\Item\BaseItem;

trait Fetchable
{
    public static function all(array $parameters = [])
    {
        $document = self::getRepository()->all($parameters);
        $data = $document->getData();
        if (null !== $data) {
            foreach ($data as $item) {
                if ($item instanceof BaseItem) {
                    $item->syncPersistedState();
                }
            }
        }

        return $document;
    }

    public static function find(string $uuid, array $parameters = [])
    {
        $document = self::getRepository()->find($uuid, $parameters);
        $data = $document->getData();
        if ($data instanceof BaseItem) {
            $data->syncPersistedState();
        }

        return $document;
    }
}
