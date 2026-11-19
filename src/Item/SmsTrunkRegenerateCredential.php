<?php

namespace Didww\Item;

use Didww\Traits\Saveable;

/**
 * Regenerates the credentials of an `smpp_esme` or `http_out` SMS trunk.
 * Create it with `new` (not build()): the response carries only an id, and
 * the new credentials are read back from the trunk.
 */
class SmsTrunkRegenerateCredential extends BaseItem
{
    use Saveable;

    public static function getEndpoint(): string
    {
        return '/sms_trunk_regenerate_credentials';
    }

    protected $type = 'sms_trunk_regenerate_credentials';

    public function smsTrunk()
    {
        return $this->hasOne(SmsTrunk::class);
    }

    public function setSmsTrunk(SmsTrunk $smsTrunk)
    {
        $this->smsTrunk()->associate($smsTrunk);
    }
}
