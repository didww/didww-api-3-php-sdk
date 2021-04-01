<?php

namespace Didww\Item;

class AddressVerification extends BaseItem
{
    use \Didww\Traits\Fetchable;
    use \Didww\Traits\Saveable;

    protected $type = 'address_verifications';

    public function getServiceDescription(): string
    {
        return $this->attributes['service_description'];
    }

    public function setServiceDescription(string $serviceDescription)
    {
        $this->attributes['service_description'] = $serviceDescription;
    }

    public function getCallbackUrl(): string
    {
        return $this->attributes['callback_url'];
    }

    public function setCallbackUrl(string $callbackUrl)
    {
        $this->attributes['callback_url'] = $callbackUrl;
    }

    public function getCallbackMethod(): string
    {
        return $this->attributes['callback_method'];
    }

    public function setCallbackMethod(string $callbackMethod)
    {
        $this->attributes['callback_method'] = $callbackMethod;
    }

    public function getStatus(): string
    {
        return $this->attributes['status'];
    }

    public function getRejectReasons(): string
    {
        return $this->attributes['reject_reasons'];
    }

    public function getCreatedAt(): \DateTime
    {
        return new \DateTime($this->attributes['created_at']);
    }

    /** @return array [
     * ]
     * 'service_description' => string
     * 'callback_url' => string
     * 'callback_method' => string
     * 'status' => string
     * 'reject_reasons' => string
     * 'created_at' => string // creation timestamp
     */
    public function getAttributes(): array
    {
        return parent::getAttributes();
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
