<?php

namespace Didww\Item;

use Didww\Traits\Saveable;

/**
 * Validates a prospective emergency calling service order against
 * an EmergencyRequirement, given an Address and an Identity.
 *
 * A successful POST returns 201 Created with the validation resource
 * (id mirrors the submitted emergency_requirement_id).
 * Introduced in API 2026-04-16.
 */
class EmergencyRequirementValidation extends BaseItem
{
    use Saveable;

    public static function getEndpoint(): string
    {
        return '/emergency_requirement_validations';
    }

    protected $type = 'emergency_requirement_validations';

    public function emergencyRequirement()
    {
        return $this->hasOne(EmergencyRequirement::class);
    }

    public function setEmergencyRequirement(EmergencyRequirement $emergencyRequirement)
    {
        $this->emergencyRequirement()->associate($emergencyRequirement);
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
