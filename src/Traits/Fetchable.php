<?php

namespace Didww\Traits;

trait Fetchable
{
    public static function all(array $parameters = [])
    {
        return self::getRepository()->all($parameters);
    }

    public static function find(string $uuid, array $parameters = [])
    {
        return self::getRepository()->find($uuid, $parameters);
    }
}
