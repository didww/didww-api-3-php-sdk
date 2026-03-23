<?php

namespace Didww\Item;

use Didww\Traits\Deletable;
use Didww\Traits\Saveable;

class PermanentSupportingDocument extends BaseItem
{
    use Saveable;
    use Deletable;

    protected $type = 'permanent_supporting_documents';

    public function getCreatedAt(): ?\DateTime
    {
        return $this->dateAttribute('created_at');
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
