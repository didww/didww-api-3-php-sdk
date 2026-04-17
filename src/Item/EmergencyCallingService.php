<?php

namespace Didww\Item;

use Didww\Enum\EmergencyCallingServiceStatus;
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

    /** @return string Human-readable name for the calling service subscription. */
    public function getName(): string
    {
        return $this->attributes['name'];
    }

    /** @return string|null Server-assigned reference code. */
    public function getReference(): ?string
    {
        return $this->attribute('reference');
    }

    /**
     * @return EmergencyCallingServiceStatus one of: active, canceled,
     *                                       changes required, in process, new, pending update
     */
    public function getStatus(): EmergencyCallingServiceStatus
    {
        return $this->enumAttribute('status', EmergencyCallingServiceStatus::class);
    }

    public function isActive(): bool
    {
        return EmergencyCallingServiceStatus::ACTIVE === $this->getStatus();
    }

    public function isCanceled(): bool
    {
        return EmergencyCallingServiceStatus::CANCELED === $this->getStatus();
    }

    public function isChangesRequired(): bool
    {
        return EmergencyCallingServiceStatus::CHANGES_REQUIRED === $this->getStatus();
    }

    public function isInProcess(): bool
    {
        return EmergencyCallingServiceStatus::IN_PROCESS === $this->getStatus();
    }

    public function isNew(): bool
    {
        return EmergencyCallingServiceStatus::NEW === $this->getStatus();
    }

    public function isPendingUpdate(): bool
    {
        return EmergencyCallingServiceStatus::PENDING_UPDATE === $this->getStatus();
    }

    /** @return \DateTime|null Timestamp when the service became active. Null while pending. */
    public function getActivatedAt()
    {
        return $this->dateAttribute('activated_at');
    }

    /** @return \DateTime|null Timestamp when the service was canceled. Null when active. */
    public function getCanceledAt()
    {
        return $this->dateAttribute('canceled_at');
    }

    /** @return \DateTime|null */
    public function getCreatedAt()
    {
        return $this->dateAttribute('created_at');
    }

    /** @return \DateTime|null Next renewal date. Null when canceled. */
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

    public function address()
    {
        return $this->hasOne(Address::class);
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
