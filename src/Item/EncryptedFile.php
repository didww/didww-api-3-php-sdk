<?php

namespace Didww\Item;

use Didww\Traits\Deletable;
use Didww\Traits\Fetchable;
use Didww\UploadResult;

class EncryptedFile extends BaseItem
{
    use Fetchable;
    use Deletable;

    public static function getEndpoint(): string
    {
        return '/encrypted_files';
    }

    protected $type = 'encrypted_files';

    public function getDescription(): ?string
    {
        return $this->attribute('description');
    }

    public function getExpiresAt(): ?\DateTime
    {
        return $this->dateAttribute('expires_at');
    }

    /**
     * POST /v3/encrypted_files with the multipart fields
     * encrypted_files[encryption_fingerprint], encrypted_files[file] and
     * encrypted_files[description].
     *
     * The body is built in memory, so the upload is sent once more with a new
     * access token when the API refuses the current one. A transport failure
     * gives an UploadResult with response code 0.
     *
     * @throws \Didww\Exception\DidwwAuthException when no access token can be obtained
     */
    public static function upload(string $fingerprint, string $fileContent, ?string $description = null, $boundary = null): UploadResult
    {
        if (null == $boundary) {
            $boundary = uniqid();
        }
        $delimiter = '-------------'.$boundary;

        $fields = [
            ['name' => 'encrypted_files[encryption_fingerprint]', 'data' => $fingerprint],
        ];
        if (null !== $description) {
            $fields[] = [
                'name' => 'encrypted_files[description]',
                'data' => $description,
            ];
        }
        $files = [
            [
                'name' => 'encrypted_files[file]',
                'data' => $fileContent,
            ],
        ];

        $postData = EncryptedFile::buildDataFiles($delimiter, $files, $fields);

        try {
            $response = \Didww\Configuration::getHttpClient()->request('POST', \Didww\Configuration::getBaseUri().'/encrypted_files', [
                'headers' => [
                    'Content-Type' => 'multipart/form-data; boundary='.$delimiter,
                    'Accept' => 'application/json',
                ],
                'body' => $postData,
                'allow_redirects' => false,
                'http_errors' => false,
                'cookies' => false,
            ]);
        } catch (\GuzzleHttp\Exception\TransferException) {
            return new UploadResult(false, 0);
        }

        return new UploadResult((string) $response->getBody(), $response->getStatusCode());
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
