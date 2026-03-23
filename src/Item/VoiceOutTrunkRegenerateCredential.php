<?php

namespace Didww\Item;

use Didww\Traits\Saveable;

class VoiceOutTrunkRegenerateCredential extends BaseItem
{
    use Saveable;

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
