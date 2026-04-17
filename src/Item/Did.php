<?php

namespace Didww\Item;

use Didww\Traits\Fetchable;
use Didww\Traits\Saveable;

class Did extends BaseItem
{
    use Saveable;
    use Fetchable;

    public static function getEndpoint(): string
    {
        return '/dids';
    }

    protected $type = 'dids';

    protected $visible = [
        'billing_cycles_count',
        'capacity_limit',
        'description',
        'terminated',
        'dedicated_channels_count',
    ];

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

    public function setDescription(?string $desc)
    {
        $this->attributes['description'] = $desc;
    }

    public function getDescription(): ?string
    {
        return $this->attribute('description');
    }

    public function setBillingCyclesCount(?int $billingCyclesCount)
    {
        $this->attributes['billing_cycles_count'] = $billingCyclesCount;
    }

    public function getBillingCyclesCount(): ?int
    {
        return $this->attribute('billing_cycles_count');
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

    public function getEmergencyEnabled(): ?bool
    {
        return $this->attribute('emergency_enabled');
    }

    public function getCreatedAt()
    {
        return $this->dateAttribute('created_at');
    }

    public function getExpiresAt()
    {
        return $this->dateAttribute('expires_at');
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

    public function addressVerification()
    {
        return $this->hasOne(AddressVerification::class);
    }

    public function emergencyCallingService()
    {
        return $this->hasOne(EmergencyCallingService::class);
    }

    public function setEmergencyCallingService(?EmergencyCallingService $emergencyCallingService)
    {
        if ($emergencyCallingService === null) {
            $this->emergencyCallingService()->dissociate();
        } else {
            $this->emergencyCallingService()->associate($emergencyCallingService);
        }
    }

    public function emergencyVerification()
    {
        return $this->hasOne(EmergencyVerification::class);
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
}
