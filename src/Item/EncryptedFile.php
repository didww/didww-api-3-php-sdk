<?php

namespace Didww\Item;

class EncryptedFile extends BaseItem
{
    use \Didww\Traits\Fetchable;
    use \Didww\Traits\Deletable;

    protected $type = 'encrypted_files';

    public function getDescription(): string
    {
        return $this->attributes['description'];
    }

    public function getExpireAt(): \DateTime
    {
        return new \DateTime($this->attributes['expire_at']);
    }

    public function getCreatedAt(): \DateTime
    {
        return new \DateTime($this->attributes['created_at']);
    }

    /** @return array [
     * ]
     * 'description' => string
     * 'expire_at' => string // timestamp when file will be deleted
     * 'created_at' => string // creation timestamp
     */
    public function getAttributes(): array
    {
        return parent::getAttributes();
    }

    // TODO: create via multipart form
    // POST /v3/encrypted_files
    // encrypted_files[encryption_fingerprint] required
    // encrypted_files[items][][file] required
    // encrypted_files[items][][description]
}
