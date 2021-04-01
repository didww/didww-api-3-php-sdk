<?php

namespace Didww\Item;

class Address extends BaseItem
{
    use \Didww\Traits\Fetchable;
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Deletable;

    protected $type = 'addresses';

    public function getCityName(): string
    {
        return $this->attributes['city_name'];
    }

    public function setCityName(string $cityName)
    {
        $this->attributes['city_name'] = $cityName;
    }

    public function getPostalCode(): string
    {
        return $this->attributes['postal_code'];
    }

    public function setPostalCode(string $postalCode)
    {
        $this->attributes['postal_code'] = $postalCode;
    }

    public function getAddress(): string
    {
        return $this->attributes['address'];
    }

    public function setAddress(string $address)
    {
        $this->attributes['address'] = $address;
    }

    public function getDescription(): string
    {
        return $this->attributes['description'];
    }

    public function setDescription(string $description)
    {
        $this->attributes['description'] = $description;
    }

    public function getCreatedAt(): \DateTime
    {
        return new \DateTime($this->attributes['created_at']);
    }

    /** @return array [
     * ]
     * 'city_name' => string
     * 'postal_code' => string
     * 'address' => string
     * 'description' => string // custom description
     * 'created_at' => string // creation timestamp
     */
    public function getAttributes(): array
    {
        return parent::getAttributes();
    }

    public function country()
    {
        return $this->hasOne(Country::class);
    }

    public function setCountry(Country $country)
    {
        $this->country()->associate($country);
    }

    public function identity()
    {
        return $this->hasOne(Identity::class);
    }

    public function setIdentity(Identity $identity)
    {
        $this->identity()->associate($identity);
    }

    public function proofs()
    {
        return $this->hasMany(Proof::class);
    }

    protected function getWhiteListAttributesKeys(): array
    {
        return [
            'city_name',
            'postal_code',
            'address',
            'description',
        ];
    }
}
