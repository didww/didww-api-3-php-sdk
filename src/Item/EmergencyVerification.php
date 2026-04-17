<?php

namespace Didww\Item;

use Didww\Enum\EmergencyVerificationStatus;
use Didww\Traits\Fetchable;
use Didww\Traits\HasExternalReferenceId;
use Didww\Traits\Saveable;

/**
 * Verification record for an emergency calling service.
 * Supported operations: index, show, create. Introduced in API 2026-04-16.
 */
class EmergencyVerification extends BaseItem
{
    use Fetchable;
    use Saveable;
    use HasExternalReferenceId;

    public static function getEndpoint(): string
    {
        return '/emergency_verifications';
    }

    protected $type = 'emergency_verifications';

    protected $visible = [
        'callback_url',
        'callback_method',
        'external_reference_id',
    ];

    public function getReference(): ?string
    {
        return $this->attribute('reference');
    }

    public function getStatus(): EmergencyVerificationStatus|string
    {
        return $this->enumAttribute('status', EmergencyVerificationStatus::class);
    }

    public function isPending(): bool
    {
        return $this->getStatus() === EmergencyVerificationStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->getStatus() === EmergencyVerificationStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->getStatus() === EmergencyVerificationStatus::REJECTED;
    }

    public function getRejectReasons(): ?array
    {
        return $this->attribute('reject_reasons');
    }

    public function getRejectComment(): ?string
    {
        return $this->attribute('reject_comment');
    }

    public function getCallbackUrl(): ?string
    {
        return $this->attribute('callback_url');
    }

    public function setCallbackUrl(?string $callbackUrl)
    {
        $this->attributes['callback_url'] = $callbackUrl;
    }

    public function getCallbackMethod(): ?string
    {
        return $this->attribute('callback_method');
    }

    public function setCallbackMethod(?string $callbackMethod)
    {
        $this->attributes['callback_method'] = $callbackMethod;
    }

    public function getCreatedAt()
    {
        return $this->dateAttribute('created_at');
    }

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    public function setAddress(Address $address)
    {
        $this->address()->associate($address);
    }

    public function emergencyCallingService()
    {
        return $this->hasOne(EmergencyCallingService::class);
    }

    public function setEmergencyCallingService(EmergencyCallingService $emergencyCallingService)
    {
        $this->emergencyCallingService()->associate($emergencyCallingService);
    }

    public function dids()
    {
        return $this->hasMany(Did::class);
    }

    public function setDids(\Swis\JsonApi\Client\Collection $dids)
    {
        $this->dids()->associate($dids);
    }
}
