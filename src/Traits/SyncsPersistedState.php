<?php

namespace Didww\Traits;

use Didww\Item\BaseItem;

trait SyncsPersistedState
{
    protected static function syncDocumentItems($document): void
    {
        $data = $document->getData();
        if ($data instanceof BaseItem) {
            $data->syncPersistedState();
        } elseif (null !== $data) {
            foreach ($data as $item) {
                if ($item instanceof BaseItem) {
                    $item->syncPersistedState();
                }
            }
        }
        foreach ($document->getIncluded() as $included) {
            if ($included instanceof BaseItem) {
                $included->syncPersistedState();
            }
        }
    }
}
