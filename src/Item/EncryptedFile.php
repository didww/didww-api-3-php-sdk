<?php

namespace Didww\Item;

use Didww\UploadResult;

class EncryptedFile extends BaseItem
{
    use \Didww\Traits\Fetchable;
    use \Didww\Traits\Deletable;

    protected $type = 'encrypted_files';

    public function getDescription(): ?string
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

    /* POST /v3/encrypted_files
     * encrypted_files[encryption_fingerprint] required
     * encrypted_files[items][][file] required
     * encrypted_files[items][][description]
     */
    public static function upload(string $fingerprint, array $filesContent, array $descriptions = [], $boundary = null): UploadResult
    {
        $apiKey = \Didww\Configuration::getApiKey();
        $baseUri = \Didww\Configuration::getBaseUri();
        $url = $baseUri.'/encrypted_files';

        $curl = curl_init();
        if (null == $boundary) {
            $boundary = uniqid();
        }
        $delimiter = '-------------'.$boundary;

        $fields = [
            ['name' => 'encrypted_files[encryption_fingerprint]', 'data' => $fingerprint],
        ];
        $files = [];
        foreach ($descriptions as &$desc) {
            array_push($fields, [
                'name' => 'encrypted_files[items][][description]',
                'data' => $desc,
            ]);
        }
        foreach ($filesContent as &$binary) {
            array_push($files, [
                'name' => 'encrypted_files[items][][file]',
                'data' => $binary,
            ]);
        }

        $postData = EncryptedFile::buildDataFiles($delimiter, $files, $fields);

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => [
                "Api-Key: $apiKey",
                'Content-Type: multipart/form-data; boundary='.$delimiter,
                'Content-Length: '.strlen($postData),
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        return new UploadResult($response, $responseCode);
    }

    private static function buildDataFiles(string $delimiter, array $files, array $fields): string
    {
        $data = '';
        $eol = "\r\n";

        foreach ($fields as $field) {
            $data .= '--'.$delimiter.$eol
                .'Content-Disposition: form-data; name="'.$field['name'].'"'.$eol.$eol
                .$field['data'].$eol;
        }
        foreach ($files as $file) {
            $filename = array_key_exists('filename', $file) ? $file['filename'] : 'file.enc';
            $contentType = array_key_exists('content-type', $file) ? $file['content-type'] : 'application/octet-stream';
            $data .= '--'.$delimiter.$eol
                .'Content-Disposition: form-data; name="'.$file['name'].'"; filename="'.$filename.'"'.$eol
                .'Content-Type: '.$contentType.$eol
                .'Content-Transfer-Encoding: binary'.$eol.$eol
                .$file['data'].$eol;
        }
        $data .= '--'.$delimiter.'--'.$eol;

        return $data;
    }
}
