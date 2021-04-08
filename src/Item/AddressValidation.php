<?php

namespace Didww\Item;

class AddressValidation extends BaseItem
{
    use \Didww\Traits\Saveable;

    protected $type = 'address_validations';

    public function requirement()
    {
        return $this->hasOne(Requirement::class);
    }

    public function setRequirement(Requirement $requirement)
    {
        $this->requirement()->associate($requirement);
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
