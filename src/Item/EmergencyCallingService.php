<?php

namespace Didww\Item;

use Didww\Traits\Deletable;
use Didww\Traits\Fetchable;

/**
 * Customer-owned subscription to emergency calling on one or more DIDs.
 * Supported operations: index, show, destroy. Introduced in API 2026-04-16.
 */
class EmergencyCallingService extends BaseItem
{
    use Fetchable;
    use Deletable;

    public static function getEndpoint(): string
    {
        return '/emergency_calling_services';
    }

    protected $type = 'emergency_calling_services';

    public function getName(): string
    {
        return $this->attributes['name'];
    }

    public function getReference(): ?string
    {
        return $this->attribute('reference');
    }

    public function getStatus(): string
    {
        return $this->attributes['status'];
    }

    public function getActivatedAt()
    {
        return $this->dateAttribute('activated_at');
    }

    public function getCanceledAt()
    {
        return $this->dateAttribute('canceled_at');
    }

    public function getCreatedAt()
    {
        return $this->dateAttribute('created_at');
    }

    public function getRenewDate()
    {
        return $this->dateAttribute('renew_date');
    }

    public function country()
    {
        return $this->hasOne(Country::class);
    }

    public function didGroupType()
    {
        return $this->hasOne(DidGroupType::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    public function emergencyRequirement()
    {
        return $this->hasOne(EmergencyRequirement::class);
    }

    public function emergencyVerification()
    {
        return $this->hasOne(EmergencyVerification::class);
    }

    public function dids()
    {
        return $this->hasMany(Did::class);
    }
}
