<?php

namespace Didww\Item;

use Didww\Enum\NumberType;
use Didww\Traits\Fetchable;

/**
 * Requirements that must be satisfied before ordering an emergency
 * calling service for a given country/number_type. Introduced in API 2026-04-16.
 *
 * Resource-level meta carries: setup_price, monthly_price (when customer has a matching plan).
 * Access via $requirement->getMeta() (inherited from SWIS Item).
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

    /**
     * Null when the country does not accept a personal identity for emergency calling.
     */
    public function getPersonalAreaLevel(): ?string
    {
        return $this->attribute('personal_area_level');
    }

    /**
     * Null when the country does not accept a business identity for emergency calling.
     */
    public function getBusinessAreaLevel(): ?string
    {
        return $this->attribute('business_area_level');
    }

    /**
     * Address fields that must be filled in, by the field names as the API
     * spells them (`area`). Empty when none are.
     *
     * @return list<string>
     */
    public function getAddressMandatoryFields(): array
    {
        return $this->attributes['address_mandatory_fields'];
    }

    /**
     * Fields that must be filled in for a personal identity, by the field
     * names as the API spells them, among `birth_date`, `country`,
     * `id_number`, `personal_tax_id` and `contact_email`. Empty when none are.
     *
     * @return list<string>
     */
    public function getPersonalMandatoryFields(): array
    {
        return $this->attributes['personal_mandatory_fields'];
    }

    /**
     * Fields that must be filled in for a business identity, by the field
     * names as the API spells them, among `id_number`, `tax_id`
     * (Identity::getVatId()), `country`, `company_reg_number`,
     * `personal_tax_id`, `contact_email` and `birth_date`. Empty when none
     * are.
     *
     * @return list<string>
     */
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

    public function getNumberType(): NumberType|string|null
    {
        return $this->enumAttribute('number_type', NumberType::class);
    }
}
