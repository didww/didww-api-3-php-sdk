<?php

namespace Didww\Item;

class VoiceOutTrunkRegenerateCredential extends BaseItem
{
    use \Didww\Traits\Saveable;

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
