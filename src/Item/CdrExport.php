<?php

namespace Didww\Item;

class CdrExport extends BaseItem
{
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Fetchable;

    protected $type = 'cdr_exports';

    private $filters = [];

    public function setFilerDidNumber($didNumber)
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

    public function toJsonApiArray(): array
    {
        $data = parent::toJsonApiArray();
        $data['attributes'] = ['filters' => $this->filters];

        return $data;
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

    protected function getWhiteListAttributesKeys()
    {
        return [
         'filters',
       ];
    }
}
