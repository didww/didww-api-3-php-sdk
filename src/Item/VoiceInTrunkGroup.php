<?php

namespace Didww\Item;

use Didww\Traits\Deletable;
use Didww\Traits\Fetchable;
use Didww\Traits\Saveable;

class VoiceInTrunkGroup extends BaseItem
{
    use Fetchable;
    use Saveable;
    use Deletable;

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

    public function getExternalReferenceId(): ?string
    {
        return $this->attribute('external_reference_id');
    }

    public function setExternalReferenceId(?string $externalReferenceId)
    {
        $this->attributes['external_reference_id'] = $externalReferenceId;
    }

    public function voiceInTrunks()
    {
        return $this->hasMany(VoiceInTrunk::class);
    }

    public function setVoiceInTrunks(\Swis\JsonApi\Client\Collection $voiceInTrunks)
    {
        $this->voiceInTrunks()->associate($voiceInTrunks);
    }
}
