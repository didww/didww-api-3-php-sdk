<?php

namespace Didww\Item;

class PermanentSupportingDocument extends BaseItem
{
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Deletable;

    protected $type = 'permanent_supporting_documents';

    public function getCreatedAt(): \DateTime
    {
        return new \DateTime($this->attributes['created_at']);
    }

    /** @return array [
     * ]
     * 'created_at' => string // creation timestamp
     */
    public function getAttributes(): array
    {
        return parent::getAttributes();
    }

    public function identity()
    {
        return $this->hasOne(Identity::class);
    }

    public function setIdentity(Identity $identity)
    {
        $this->identity()->associate($identity);
    }

    public function template()
    {
        return $this->hasOne(SupportingDocumentTemplate::class);
    }

    public function setTemplate(SupportingDocumentTemplate $template)
    {
        $this->template()->associate($template);
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
