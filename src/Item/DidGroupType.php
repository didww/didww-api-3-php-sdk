<?php

namespace Didww\Item;

use Didww\Traits\Fetchable;

class DidGroupType extends BaseItem
{
    use Fetchable;

    public static function getEndpoint(): string
    {
        return '/did_group_types';
    }

    protected $type = 'did_group_types';
}
