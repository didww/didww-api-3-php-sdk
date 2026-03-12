<?php

namespace Didww\Item;

use Didww\Enum\AddressVerificationStatus;
use Didww\Enum\CallbackMethod;

class AddressVerification extends BaseItem
{
    use \Didww\Traits\Fetchable;
    use \Didww\Traits\Saveable;

    protected $type = 'address_verifications';

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

    public function getRejectReasons(): ?array
    {
        $reasons = $this->attribute('reject_reasons');
        if (null === $reasons) {
            return null;
        }

        return explode('; ', $reasons);
    }

    public function getReference(): ?string
    {
        return $this->attribute('reference');
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

    protected function getWhiteListAttributesKeys(): array
    {
        return [
            'service_description',
            'callback_url',
            'callback_method',
        ];
    }
}
