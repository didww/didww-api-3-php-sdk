<?php

namespace Didww\Traits;

trait Saveable
{
    use SyncsPersistedState;

    public function save(array $parameters = [])
    {
        $document = self::getRepository()->save($this, $parameters);
        self::syncDocumentItems($document);

        return $document;
    }
}
