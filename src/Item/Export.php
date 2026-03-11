<?php

namespace Didww\Item;

use Didww\Enum\CallbackMethod;
use Didww\Enum\ExportType;

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

    public function getExportType(): ExportType
    {
        return $this->enumAttribute('export_type', ExportType::class);
    }

    public function setExportType(ExportType|string $exportType)
    {
        $this->attributes['export_type'] = $exportType instanceof ExportType ? $exportType->value : $exportType;
    }

    public function getCallbackUrl(): ?string
    {
        return $this->attribute('callback_url');
    }

    public function setCallbackUrl(string $callbackUrl)
    {
        $this->attributes['callback_url'] = $callbackUrl;
    }

    public function getCallbackMethod(): ?CallbackMethod
    {
        return $this->enumAttribute('callback_method', CallbackMethod::class);
    }

    public function setCallbackMethod(CallbackMethod|string $callbackMethod)
    {
        $this->attributes['callback_method'] = $callbackMethod instanceof CallbackMethod ? $callbackMethod->value : $callbackMethod;
    }

    public function download($dest)
    {
        $apiKey = \Didww\Configuration::getCredentials()->getApiKey();

        $options = [
            CURLOPT_HTTPHEADER => ["api-key: $apiKey"],
            CURLOPT_FILE => is_resource($dest) ? $dest : fopen($dest, 'w'),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $this->getAttributes()['url'],
            CURLOPT_FAILONERROR => true, // HTTP code > 400 will throw curl error
        ];
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $return = curl_exec($ch);
        if (false === $return) {
            return curl_error($ch);
        }

        return true;
    }

    public function downloadAndDecompress($dest)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'didww_export_');
        if (false === $tmpFile) {
            return 'Failed to create temporary file';
        }
        $result = $this->download($tmpFile);
        if (true !== $result) {
            @unlink($tmpFile);

            return $result;
        }

        $gz = gzopen($tmpFile, 'rb');
        if (false === $gz) {
            unlink($tmpFile);

            return 'Failed to open gzip file for decompression';
        }

        $destHandle = is_resource($dest) ? $dest : fopen($dest, 'w');
        if (!is_resource($dest) && false === $destHandle) {
            gzclose($gz);
            unlink($tmpFile);

            return 'Failed to open destination file for writing';
        }
        while (!gzeof($gz)) {
            fwrite($destHandle, gzread($gz, 8192));
        }
        gzclose($gz);
        if (!is_resource($dest)) {
            fclose($destHandle);
        }
        unlink($tmpFile);

        return true;
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
