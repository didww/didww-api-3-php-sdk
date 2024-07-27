<?php

namespace Didww\Item;

class VoiceInTrunkGroup extends BaseItem
{
    use \Didww\Traits\Fetchable;
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Deletable;

    protected $type = 'voice_in_trunk_groups';

    public function voiceInTrunks()
    {
        return $this->hasMany(VoiceInTrunk::class);
    }

    public function setVoiceInTrunks(\Swis\JsonApi\Client\Collection $voiceInTrunks)
    {
        $this->voiceInTrunks()->associate($voiceInTrunks);
    }

    protected function getWhiteListAttributesKeys()
    {
        return [
            'capacity_limit',
            'name',
        ];
    }
}
