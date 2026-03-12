<?php

namespace Didww\Item;

use Didww\Enum\AreaLevel;
use Didww\Enum\IdentityType;

class Requirement extends BaseItem
{
    use \Didww\Traits\Fetchable;

    protected $type = 'requirements';

    public function getIdentityType(): IdentityType
    {
        return $this->enumAttribute('identity_type', IdentityType::class);
    }

    public function getPersonalAreaLevel(): AreaLevel
    {
        return $this->enumAttribute('personal_area_level', AreaLevel::class);
    }

    public function getBusinessAreaLevel(): AreaLevel
    {
        return $this->enumAttribute('business_area_level', AreaLevel::class);
    }

    public function getAddressAreaLevel(): AreaLevel
    {
        return $this->enumAttribute('address_area_level', AreaLevel::class);
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
