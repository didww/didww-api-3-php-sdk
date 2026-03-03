<?php

namespace Didww\Traits;

trait Fetchable
{
    use SyncsPersistedState;

    public static function all(array $parameters = [])
    {
        $document = self::getRepository()->all($parameters);
        self::syncDocumentItems($document);

        return $document;
    }

    public static function find(string $uuid, array $parameters = [])
    {
        $document = self::getRepository()->find($uuid, $parameters);
        self::syncDocumentItems($document);

        return $document;
    }
}
