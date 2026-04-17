<?php

namespace Didww\Item;

use Didww\Enum\AddressVerificationStatus;
use Didww\Enum\CallbackMethod;
use Didww\Traits\Fetchable;
use Didww\Traits\HasExternalReferenceId;
use Didww\Traits\Saveable;

class AddressVerification extends BaseItem
{
    use Fetchable;
    use Saveable;
    use HasExternalReferenceId;

    public static function getEndpoint(): string
    {
        return '/address_verifications';
    }

    protected $type = 'address_verifications';

    protected $visible = [
        'service_description',
        'callback_url',
        'callback_method',
        'external_reference_id',
    ];

    public function getServiceDescription(): ?string
    {
        return $this->attribute('service_description');
    }

    public function setServiceDescription(string $serviceDescription)
    {
        $this->attributes['service_description'] = $serviceDescription;
    }

    public function getCallbackUrl(): ?string
    {
        return $this->attribute('callback_url');
    }

    public function setCallbackUrl(string $callbackUrl)
    {
        $this->attributes['callback_url'] = $callbackUrl;
    }

    public function getCallbackMethod(): ?CallbackMethod
    {
        return $this->enumAttribute('callback_method', CallbackMethod::class);
    }

    public function setCallbackMethod(CallbackMethod|string $callbackMethod)
    {
        $this->setEnumAttribute('callback_method', $callbackMethod);
    }

    public function getStatus(): AddressVerificationStatus
    {
        return $this->enumAttribute('status', AddressVerificationStatus::class);
    }

    public function isPending(): bool
    {
        return $this->getStatus() === AddressVerificationStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->getStatus() === AddressVerificationStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->getStatus() === AddressVerificationStatus::REJECTED;
    }

    public function getRejectReasons(): ?array
    {
        return $this->attribute('reject_reasons');
    }

    public function getReference(): ?string
    {
        return $this->attribute('reference');
    }

    public function getRejectComment(): ?string
    {
        return $this->attribute('reject_comment');
    }

    public function getCreatedAt(): ?\DateTime
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

    public function dids()
    {
        return $this->hasMany(Did::class);
    }

    public function setDids(\Swis\JsonApi\Client\Collection $dids)
    {
        $this->dids()->associate($dids);
    }
}
