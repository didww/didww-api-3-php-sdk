<?php

namespace Didww\Item;

class Proof extends BaseItem
{
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Deletable;

    protected $type = 'proofs';

    public function getCreatedAt(): \DateTime
    {
        return new \DateTime($this->attributes['created_at']);
    }

    public function getExpiresAt(): \DateTime
    {
        return new \DateTime($this->attributes['expires_at']);
    }

    /** @return array [
     * ]
     * 'created_at' => string // creation timestamp
     * 'expires_at' => string // creation timestamp
     */
    public function getAttributes(): array
    {
        return parent::getAttributes();
    }

    public function entity()
    {
        return $this->morphTo();
    }

    public function setEntity($entity)
    {
        $this->entity()->associate($entity);
    }

    public function proofType()
    {
        return $this->hasOne(ProofType::class);
    }

    public function setProofType(ProofType $proofType)
    {
        $this->proofType()->associate($proofType);
    }

    public function files()
    {
        return $this->hasMany(EncryptedFile::class);
    }

    public function setFiles(\Swis\JsonApi\Client\Collection $files)
    {
        $this->files()->associate($files);
    }
}
