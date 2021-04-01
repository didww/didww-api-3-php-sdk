<?php

namespace Didww\Item;

class SupportingDocumentTemplate extends BaseItem
{
    use \Didww\Traits\Fetchable;

    protected $type = 'supporting_document_templates';

    public function getName(): string
    {
        return $this->attributes['name'];
    }

    public function getPermanent(): bool
    {
        return $this->attributes['permanent'];
    }

    public function getTemplateType(): string
    {
        return $this->attributes['template_type'];
    }

    public function getUrl(): string
    {
        return $this->attributes['url'];
    }

    /** @return array [
     * ]
     * 'name' => string // friendly name
     * 'permanent' => bool // if true than should be used as identity permanent document template, otherwise false
     * 'template_type' => string // 'Address' or 'Porting'
     * 'url' => string // public URL for downloading document form
     */
    public function getAttributes(): array
    {
        return parent::getAttributes();
    }
}
