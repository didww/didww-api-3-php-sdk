<?php

namespace Didww\Item;

use Didww\Traits\Saveable;

class AddressRequirementValidation extends BaseItem
{
    use Saveable;

    public static function getEndpoint(): string
    {
        return '/address_requirement_validations';
    }

    protected $type = 'address_requirement_validations';

    public function addressRequirement()
    {
        return $this->hasOne(AddressRequirement::class);
    }

    public function setAddressRequirement(AddressRequirement $addressRequirement)
    {
        $this->addressRequirement()->associate($addressRequirement);
    }

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    public function setAddress(Address $address)
    {
        $this->address()->associate($address);
    }

    public function identity()
    {
        return $this->hasOne(Identity::class);
    }

    public function setIdentity(Identity $identity)
    {
        $this->identity()->associate($identity);
    }
}
