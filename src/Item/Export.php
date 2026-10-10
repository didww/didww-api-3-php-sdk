<?php

namespace Didww\Item;

use Didww\Enum\CallbackMethod;
use Didww\Enum\ExportStatus;
use Didww\Enum\ExportType;
use Didww\Traits\Fetchable;
use Didww\Traits\HasExternalReferenceId;
use Didww\Traits\Saveable;

class Export extends BaseItem
{
    use Saveable;
    use Fetchable;
    use HasExternalReferenceId;

    public static function getEndpoint(): string
    {
        return '/exports';
    }

    protected $type = 'exports';

    protected $visible = [
        'filters',
        'export_type',
        'callback_url',
        'callback_method',
        'external_reference_id',
    ];

    private $filters = [];

    public function setFilterDidNumber($didNumber)
    {
        $this->filters['did_number'] = $didNumber;
    }

    public function setFilterFrom(string $from)
    {
        $this->filters['from'] = $from;
    }

    public function setFilterTo(string $to)
    {
        $this->filters['to'] = $to;
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

    public function getStatus(): ExportStatus
    {
        return $this->enumAttribute('status', ExportStatus::class);
    }

    public function isPending(): bool
    {
        return ExportStatus::PENDING === $this->getStatus();
    }

    public function isProcessing(): bool
    {
        return ExportStatus::PROCESSING === $this->getStatus();
    }

    public function isCompleted(): bool
    {
        return ExportStatus::COMPLETED === $this->getStatus();
    }

    public function getExportType(): ExportType
    {
        return $this->enumAttribute('export_type', ExportType::class);
    }

    public function setExportType(ExportType|string $exportType)
    {
        $this->setEnumAttribute('export_type', $exportType);
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
        $this->setEnumAttribute('callback_method', $callbackMethod);
    }

    public function download($dest)
    {
        $apiKey = \Didww\Configuration::getCredentials()->getApiKey();
        $ownHandle = !is_resource($dest);
        $destHandle = $ownHandle ? fopen($dest, 'wb') : $dest;
        if (false === $destHandle) {
            return 'Failed to open destination file for writing';
        }

        try {
            \Didww\Configuration::getHttpClient()->request('GET', $this->getAttributes()['url'], [
                'headers' => [
                    'Api-Key' => $apiKey,
                    'User-Agent' => 'didww-php-sdk/'.\Didww\Client::sdkVersion(),
                    'X-DIDWW-API-Version' => \Didww\Configuration::getCredentials()->getVersion() ?? '2026-04-16',
                ],
                'sink' => $destHandle,
                'allow_redirects' => true,
                // HTTP code >= 400 will throw a GuzzleException, same as CURLOPT_FAILONERROR before.
            ]);
            $error = null;
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $error = $e->getMessage();
        }
        // Guzzle's 'sink' stream wrapper already closes the underlying resource once
        // the response body has been written to it.
        if ($ownHandle && is_resource($destHandle)) {
            fclose($destHandle);
        }

        return $error ?? true;
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

        $gz = fopen('compress.zlib://'.$tmpFile, 'rb');
        if (false === $gz) {
            unlink($tmpFile);

            return 'Failed to open gzip file for decompression';
        }

        $ownHandle = !is_resource($dest);
        $destHandle = $ownHandle ? fopen($dest, 'wb') : $dest;
        if ($ownHandle && false === $destHandle) {
            fclose($gz);
            unlink($tmpFile);

            return 'Failed to open destination file for writing';
        }
        stream_copy_to_stream($gz, $destHandle);
        fclose($gz);
        if ($ownHandle) {
            fclose($destHandle);
        }
        unlink($tmpFile);

        return true;
    }
}
