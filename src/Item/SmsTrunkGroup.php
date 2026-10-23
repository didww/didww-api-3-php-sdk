<?php

namespace Didww\Item;

use Didww\Traits\Deletable;
use Didww\Traits\Fetchable;
use Didww\Traits\HasExternalReferenceId;
use Didww\Traits\Saveable;

/**
 * A group of SMS trunks. Deleting a group also deletes the trunks in it.
 */
class SmsTrunkGroup extends BaseItem
{
    use Fetchable;
    use Saveable;
    use Deletable;
    use HasExternalReferenceId;

    public static function getEndpoint(): string
    {
        return '/sms_trunk_groups';
    }

    protected $type = 'sms_trunk_groups';

    protected $visible = [
        'name',
        'external_reference_id',
    ];

    public function getName(): string
    {
        return $this->attributes['name'];
    }

    public function setName(string $name)
    {
        $this->attributes['name'] = $name;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->dateAttribute('created_at');
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->dateAttribute('updated_at');
    }

    /**
     * Trunks currently in the group (meta `sms_trunks_count`).
     */
    public function getSmsTrunksCount(): ?int
    {
        $count = $this->getMeta()?->toArray()['sms_trunks_count'] ?? null;

        return null === $count ? null : (int) $count;
    }

    public function smsTrunks()
    {
        return $this->hasMany(SmsTrunk::class);
    }

    /**
     * Replaces the group's trunks; an empty collection removes them all.
     * `http_out` trunks cannot join a group.
     */
    public function setSmsTrunks(\Swis\JsonApi\Client\Collection $smsTrunks)
    {
        $this->smsTrunks()->associate($smsTrunks);
    }

    public function incomingDids()
    {
        return $this->hasMany(Did::class, 'incoming_dids');
    }
}
