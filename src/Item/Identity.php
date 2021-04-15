<?php

namespace Didww\Item;

class Identity extends BaseItem
{
    use \Didww\Traits\Fetchable;
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Deletable;

    protected $type = 'identities';

    public function getFirstName(): string
    {
        return $this->attributes['first_name'];
    }

    public function setFirstName(string $firstName)
    {
        $this->attributes['first_name'] = $firstName;
    }

    public function getLastName(): string
    {
        return $this->attributes['last_name'];
    }

    public function setLastName(string $lastName)
    {
        $this->attributes['last_name'] = $lastName;
    }

    public function getPhoneNumber(): string
    {
        return $this->attributes['phone_number'];
    }

    public function setPhoneNumber(string $phoneNumber)
    {
        $this->attributes['phone_number'] = $phoneNumber;
    }

    public function getIdNumber(): ?string
    {
        return $this->attributes['id_number'];
    }

    public function setIdNumber(string $idNumber)
    {
        $this->attributes['id_number'] = $idNumber;
    }

    public function getBirthDate(): ?\DateTime
    {
        return new \DateTime($this->attributes['birth_date']);
    }

    public function setBirthDate(string $birthDate)
    {
        $this->attributes['birth_date'] = $birthDate;
    }

    public function getCompanyName(): ?string
    {
        return $this->attributes['company_name'];
    }

    public function setCompanyName(string $companyName)
    {
        $this->attributes['company_name'] = $companyName;
    }

    public function getCompanyRegNumber(): ?string
    {
        return $this->attributes['company_reg_number'];
    }

    public function setCompanyRegNumber(string $companyRegNumber)
    {
        $this->attributes['company_reg_number'] = $companyRegNumber;
    }

    public function getVatId(): ?string
    {
        return $this->attributes['vat_id'];
    }

    public function setVatId(string $vatId)
    {
        $this->attributes['vat_id'] = $vatId;
    }

    public function getDescription(): ?string
    {
        return $this->attributes['description'];
    }

    public function setDescription(string $description)
    {
        $this->attributes['description'] = $description;
    }

    public function getPersonalTaxId(): ?string
    {
        return $this->attributes['personal_tax_id'];
    }

    public function setPersonalTaxId(string $personalTaxId)
    {
        $this->attributes['personal_tax_id'] = $personalTaxId;
    }

    public function getIdentityType(): string
    {
        return $this->attributes['identity_type'];
    }

    public function setIdentityType(string $identityType)
    {
        $this->attributes['identity_type'] = $identityType;
    }

    public function getExternalReferenceId(): ?string
    {
        return $this->attributes['external_reference_id'];
    }

    public function setExternalReferenceId(string $externalReferenceId)
    {
        $this->attributes['external_reference_id'] = $externalReferenceId;
    }

    public function getCreatedAt(): \DateTime
    {
        return new \DateTime($this->attributes['created_at']);
    }

    public function getVerified(): bool
    {
        return $this->attributes['verified'];
    }

    /** @return array [
     * ]
     * 'first_name' => string
     * 'last_name' => string
     * 'phone_number' => string
     * 'id_number' => string // passport number or similar
     * 'birth_date' => string
     * 'company_name' => string // only for Business identity
     * 'company_reg_number' => string // only for Business identity
     * 'vat_id' => string // only for Business identity
     * 'description' => string // custom description
     * 'personal_tax_id' => string
     * 'identity_type' => string // 'Personal' or 'Business'
     * 'created_at' => string // creation timestamp
     * 'external_reference_id' => string // custom identifier
     * 'verified' => bool
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

    public function proofs()
    {
        return $this->hasMany(Proof::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function permanentDocuments()
    {
        return $this->hasMany(PermanentSupportingDocument::class);
    }

    protected function getWhiteListAttributesKeys(): array
    {
        return [
            'first_name',
            'last_name',
            'phone_number',
            'id_number',
            'birth_date',
            'company_name',
            'company_reg_number',
            'vat_id',
            'description',
            'personal_tax_id',
            'identity_type',
            'external_reference_id',
        ];
    }
}
