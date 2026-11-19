<?php

namespace Didww\Item;

use Didww\Traits\Saveable;

/**
 * Rotates the generated credentials of a Voice OUT trunk with
 * `credentials_and_ip` authentication; only save() of a new one is supported.
 * A trunk with `twilio` authentication is answered 404.
 */
class VoiceOutTrunkRegenerateCredential extends BaseItem
{
    use Saveable;

    public static function getEndpoint(): string
    {
        return '/voice_out_trunk_regenerate_credentials';
    }

    protected $type = 'voice_out_trunk_regenerate_credentials';

    public function voiceOutTrunk()
    {
        return $this->hasOne(VoiceOutTrunk::class);
    }

    public function setVoiceOutTrunk(VoiceOutTrunk $voiceOutTrunk)
    {
        $this->voiceOutTrunk()->associate($voiceOutTrunk);
    }
}
