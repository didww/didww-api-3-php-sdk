<?php

namespace Didww\Item;

class Proof extends BaseItem
{
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Deletable;

    protected $type = 'proofs';

    public function getCreatedAt(): ?\DateTime
    {
        return $this->dateAttribute('created_at');
    }

    public function getExpiresAt(): ?\DateTime
    {
        return $this->dateAttribute('expires_at');
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
