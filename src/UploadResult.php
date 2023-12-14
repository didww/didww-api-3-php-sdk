<?php

namespace Didww;

class UploadResult
{
    private $response;
    private $responseCode;
    private $responseData;

    public function __construct($response, $responseCode)
    {
        $this->response = $response;
        $this->responseCode = $responseCode;
        $this->responseData = null;
        if (false != $response && 500 != $responseCode) {
            $this->responseData = json_decode($this->response, true);
        }
    }

    public function success(): bool
    {
        return $this->responseCode >= 200 && $this->responseCode < 300 && false != $this->response;
    }

    public function hasErrors(): bool
    {
        return $this->responseCode >= 400 && $this->responseCode < 500;
    }

    public function getResponse(): string
    {
        return false == $this->response ? '' : $this->response;
    }

    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    public function getErrors(): array
    {
        return null == $this->responseData ? [] : $this->responseData['errors'];
    }

    public function getIds(): array
    {
        return null == $this->responseData ? [] : $this->responseData['ids'];
    }
}
