<?php

namespace Didww\Item;

class Did extends BaseItem
{
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Fetchable;

    protected $type = 'dids';

    public function setDedicatedChannelsCount(int $dedicatedChannelCount)
    {
        $this->attributes['dedicated_channels_count'] = $dedicatedChannelCount;
    }

    public function getDedicatedChannelsCount(): int
    {
        return $this->attributes['dedicated_channels_count'];
    }

    public function getTerminated(): bool
    {
        return $this->attributes['terminated'];
    }

    public function setTerminated(bool $terminated)
    {
        $this->attributes['terminated'] = $terminated;
    }

    public function setDescription(string $desc)
    {
        $this->attributes['description'] = $desc;
    }

    public function getDescription(): ?string
    {
        return $this->attributes['description'];
    }

    public function setBillingCyclesCount(?int $billingCyclesCount)
    {
        $this->attributes['billing_cycles_count'] = $billingCyclesCount;
    }

    public function getBillingCyclesCount(): ?int
    {
        return $this->attributes['billing_cycles_count'];
    }

    public function setCapacityLimit(int $capacityLimit)
    {
        $this->attributes['capacity_limit'] = $capacityLimit;
    }

    public function getCapacityLimit(): int
    {
        return $this->attributes['capacity_limit'];
    }

    public function getBlocked(): bool
    {
        return $this->attributes['blocked'];
    }

    public function getAwaitingRegistration(): bool
    {
        return $this->attributes['awaiting_registration'];
    }

    public function getNumber(): string
    {
        return $this->attributes['number'];
    }

    public function getCreatedAt()
    {
        return new \DateTime($this->attributes['created_at']);
    }

    public function getExpiresAt()
    {
        return new \DateTime($this->attributes['expires_at']);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    public function didGroup()
    {
        return $this->hasOne(DidGroup::class);
    }

    public function voiceInTrunk()
    {
        return $this->hasOne(VoiceInTrunk::class);
    }

    public function voiceInTrunkGroup()
    {
        return $this->hasOne(VoiceInTrunkGroup::class);
    }

    public function capacityPool()
    {
        return $this->hasOne(CapacityPool::class);
    }

    public function sharedCapacityGroup()
    {
        return $this->hasOne(SharedCapacityGroup::class);
    }

    public function setVoiceInTrunkGroup(VoiceInTrunkGroup $voiceInTrunkGroup)
    {
        $this->voiceInTrunkGroup()->associate($voiceInTrunkGroup);
        $this->voiceInTrunk()->dissociate();
    }

    public function setVoiceInTrunk(VoiceInTrunk $voiceInTrunk)
    {
        $this->voiceInTrunk()->associate($voiceInTrunk);
        $this->voiceInTrunkGroup()->dissociate();
    }

    public function setCapacityPool(CapacityPool $capacityPool)
    {
        $this->capacityPool()->associate($capacityPool);
    }

    public function setSharedCapacityGroup(SharedCapacityGroup $sharedCapacityGroup)
    {
        $this->sharedCapacityGroup()->associate($sharedCapacityGroup);
    }

    protected function getWhiteListAttributesKeys()
    {
        return [
         'billing_cycles_count',
         'capacity_limit',
         'description',
         'terminated',
         'dedicated_channels_count',
       ];
    }
}
