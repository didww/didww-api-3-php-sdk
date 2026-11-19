<?php

namespace Didww;

class UploadResult
{
    private $response;
    private $responseCode;
    private $responseData;
    private ?int $retryAfter;

    public function __construct($response, $responseCode, ?int $retryAfter = null)
    {
        $this->response = $response;
        $this->responseCode = $responseCode;
        $this->retryAfter = $retryAfter;
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
        return $this->responseData['errors'] ?? [];
    }

    /**
     * The Retry-After of the answer in seconds (see RetryAfter::seconds()),
     * for example with a 429 (`too_many_requests`), which is not retried; null
     * when it is missing or no answer was received.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    public function getId(): ?string
    {
        return $this->responseData['data']['id'] ?? null;
    }
}
