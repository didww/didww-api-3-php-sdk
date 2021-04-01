<?php

namespace Didww\Item;

class Requirement extends BaseItem
{
    use \Didww\Traits\Fetchable;

    protected $type = 'requirements';

    public function getIdentityType(): string
    {
        return $this->attributes['identity_type'];
    }

    public function getPersonalAreaLevel(): string
    {
        return $this->attributes['personal_area_level'];
    }

    public function getBusinessAreaLevel(): string
    {
        return $this->attributes['business_area_level'];
    }

    public function getAddressAreaLevel(): string
    {
        return $this->attributes['address_area_level'];
    }

    public function getPersonalProofQty(): int
    {
        return $this->attributes['personal_proof_qty'];
    }

    public function getBusinessProofQty(): int
    {
        return $this->attributes['business_proof_qty'];
    }

    public function getAddressProofQty(): int
    {
        return $this->attributes['address_proof_qty'];
    }

    public function getPersonalMandatoryFields(): array
    {
        return $this->attributes['personal_mandatory_fields'];
    }

    public function getBusinessMandatoryFields(): array
    {
        return $this->attributes['business_mandatory_fields'];
    }

    public function getServiceDescriptionRequired(): bool
    {
        return $this->attributes['service_description_required'];
    }

    public function getRestrictionMessage(): string
    {
        return $this->attributes['restriction_message'];
    }

    /** @return array [
     * ]
     * 'identity_type' => string
     * 'personal_area_level' => string
     * 'business_area_level' => string
     * 'address_area_level' => string
     * 'personal_proof_qty' => int
     * 'business_proof_qty' => int
     * 'address_proof_qty' => int
     * 'personal_mandatory_fields' => array[string]
     * 'business_mandatory_fields' => array[string]
     * 'service_description_required' => bool
     * 'restriction_message' => string
     */
    public function getAttributes(): array
    {
        return parent::getAttributes();
    }

    public function country()
    {
        return $this->hasOne(Country::class);
    }

    public function didGroupType()
    {
        return $this->hasOne(DidGroupType::class);
    }

    public function personalPermanentDocument()
    {
        return $this->hasOne(SupportingDocumentTemplate::class);
    }

    public function businessPermanentDocument()
    {
        return $this->hasOne(SupportingDocumentTemplate::class);
    }

    public function personalOnetimeDocument()
    {
        return $this->hasOne(SupportingDocumentTemplate::class);
    }

    public function businessOnetimeDocument()
    {
        return $this->hasOne(SupportingDocumentTemplate::class);
    }

    public function personalProofTypes()
    {
        return $this->hasMany(ProofType::class);
    }

    public function businessProofTypes()
    {
        return $this->hasMany(ProofType::class);
    }

    public function addressProofTypes()
    {
        return $this->hasMany(ProofType::class);
    }
}
