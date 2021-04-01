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

    public function download($dest)
    {
        $options = [
            CURLOPT_FILE => is_resource($dest) ? $dest : fopen($dest, 'w'),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $this->getAttributes()['url'],
            CURLOPT_VERBOSE => true,
            CURLOPT_FAILONERROR => true, // HTTP code > 400 will throw curl error
        ];
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $return = curl_exec($ch);
        if (false === $return) {
            return curl_error($ch);
        } else {
            return true;
        }
    }
}
