<?php

namespace Didww\Item;

use Didww\Traits\Deletable;
use Didww\Traits\Fetchable;
use Didww\Traits\HasExternalReferenceId;
use Didww\Traits\Saveable;

class VoiceInTrunkGroup extends BaseItem
{
    use Fetchable;
    use Saveable;
    use Deletable;
    use HasExternalReferenceId;

    public static function getEndpoint(): string
    {
        return '/voice_in_trunk_groups';
    }

    protected $type = 'voice_in_trunk_groups';

    protected $visible = [
        'capacity_limit',
        'name',
        'external_reference_id',
    ];

    public function voiceInTrunks()
    {
        return $this->hasMany(VoiceInTrunk::class);
    }

    public function setVoiceInTrunks(\Swis\JsonApi\Client\Collection $voiceInTrunks)
    {
        $this->voiceInTrunks()->associate($voiceInTrunks);
    }
}
