<?php

namespace Didww\Item;

abstract class BaseItem extends \Swis\JsonApi\Client\Item
{
    public static function build(string $uuid, array $attributes = [])
    {
        $item = new static();
        $item->fill($attributes);
        $item->setId($uuid);

        return $item;
    }

    public static function getRepository()
    {
        $repository = new \Didww\Repository(\Didww\Configuration::getDocumentClient());
        $repository->setEndpoint(static::getEndpoint());

        return $repository;
    }

    public static function getEndpoint()
    {
        return '/'.snake_case(str_plural(substr(get_called_class(), strrpos(get_called_class(), '\\') + 1)));
    }

    public function toJsonApiArray(): array
    {
        $attributes = parent::toJsonApiArray();
        $whitelist = $this->getWhiteListAttributesKeys();
        if (is_array($whitelist) && isset($attributes['attributes'])) {
            // keep only whitelisted attributes
            $attributes['attributes'] = array_intersect_key($attributes['attributes'], array_flip($whitelist));
        }

        return $attributes;
    }

    protected function getWhiteListAttributesKeys()
    {
        return null;
    }
}
