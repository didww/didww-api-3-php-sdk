<?php

namespace Didww\Item;

class Export extends BaseItem
{
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Fetchable;

    protected $type = 'exports';

    private $filters = [];

    public function setFilterDidNumber($didNumber)
    {
        $this->filters['did_number'] = $didNumber;
    }

    public function setFilterYear($year)
    {
        $this->filters['year'] = $year;
    }

    public function setFilterMonth($month)
    {
        $this->filters['month'] = $month;
    }

    public function setFilterDay($day)
    {
        $this->filters['day'] = $day;
    }

    public function setFilterVoiceOutTrunkId($voiceOutTrunkId)
    {
        $this->filters['voice_out_trunk.id'] = $voiceOutTrunkId;
    }

    public function toJsonApiArray(): array
    {
        $data = parent::toJsonApiArray();
        $data['attributes']['filters'] = $this->filters;

        return $data;
    }

    public function getExportType(): string
    {
        return $this->attributes['export_type'];
    }

    public function setExportType(string $exportType)
    {
        $this->attributes['export_type'] = $exportType;
    }

    public function getCallbackUrl(): ?string
    {
        return $this->attributes['callback_url'];
    }

    public function setCallbackUrl(string $callbackUrl)
    {
        $this->attributes['callback_url'] = $callbackUrl;
    }

    public function getCallbackMethod(): ?string
    {
        return $this->attributes['callback_method'];
    }

    public function setCallbackMethod(string $callbackMethod)
    {
        $this->attributes['callback_method'] = $callbackMethod;
    }

    public function download($dest)
    {
        $apiKey = \Didww\Configuration::getCredentials()->getApiKey();

        $options = [
         CURLOPT_HTTPHEADER => ["api-key: $apiKey"],
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

    /** @return array [
     * ]
     * 'status' => string
     * 'url' => string
     * 'callback_url' => string
     * 'callback_method' => string
     * 'export_type' => string
     * 'created_at' => string // creation timestamp
     */
    public function getAttributes(): array
    {
        return parent::getAttributes();
    }

    protected function getWhiteListAttributesKeys(): array
    {
        return [
            'filters',
            'export_type',
            'callback_url',
            'callback_method',
        ];
    }
}
