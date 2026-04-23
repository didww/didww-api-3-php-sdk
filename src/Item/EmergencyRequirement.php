<?php

namespace Didww\Item;

use Didww\Traits\Fetchable;

/**
 * Requirements that must be satisfied before ordering an emergency
 * calling service for a given country/did_group_type. Introduced in API 2026-04-16.
 */
class EmergencyRequirement extends BaseItem
{
    use Fetchable;

    public static function getEndpoint(): string
    {
        return '/emergency_requirements';
    }

    protected $type = 'emergency_requirements';

    public function getIdentityType(): string
    {
        return $this->attributes['identity_type'];
    }

    public function getAddressAreaLevel(): string
    {
        return $this->attributes['address_area_level'];
    }

    public function getPersonalAreaLevel(): string
    {
        return $this->attributes['personal_area_level'];
    }

    public function getBusinessAreaLevel(): string
    {
        return $this->attributes['business_area_level'];
    }

    public function getAddressMandatoryFields(): array
    {
        return $this->attributes['address_mandatory_fields'];
    }

    public function getPersonalMandatoryFields(): array
    {
        return $this->attributes['personal_mandatory_fields'];
    }

    public function getBusinessMandatoryFields(): array
    {
        return $this->attributes['business_mandatory_fields'];
    }

    public function getEstimateSetupTime(): string|int
    {
        return $this->attributes['estimate_setup_time'];
    }

    public function getRequirementRestrictionMessage(): ?string
    {
        return $this->attribute('requirement_restriction_message');
    }

    public function country()
    {
        return $this->hasOne(Country::class);
    }

    public function didGroupType()
    {
        return $this->hasOne(DidGroupType::class);
    }
}
