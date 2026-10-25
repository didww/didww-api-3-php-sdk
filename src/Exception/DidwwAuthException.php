<?php

namespace Didww\Exception;

/**
 * Thrown when an access token cannot be obtained from the token service. It
 * never contains the API key or a token. Use getKind() to tell the cases apart.
 */
final class DidwwAuthException extends \RuntimeException implements DidwwExceptionInterface
{
    public function __construct(
        private readonly AuthErrorKind $kind,
        private readonly ?int $statusCode = null,
        private readonly ?string $error = null,
        private readonly ?string $errorDescription = null,
        private readonly ?int $retryAfter = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(self::buildMessage($statusCode, $error, $errorDescription, $previous), 0, $previous);
    }

    public function getKind(): AuthErrorKind
    {
        return $this->kind;
    }

    /**
     * The HTTP status of the token service; null when no answer was received.
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * The "error" field of the answer, for example "invalid_client".
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * The "error_description" field of the answer.
     */
    public function getErrorDescription(): ?string
    {
        return $this->errorDescription;
    }

    /**
     * Seconds to wait before trying again; set with AuthErrorKind::RATE_LIMITED.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    private static function buildMessage(?int $statusCode, ?string $error, ?string $errorDescription, ?\Throwable $previous): string
    {
        $prefix = 'Token service: ';
        if (null !== $error && '' !== $error && null !== $errorDescription && '' !== $errorDescription) {
            return $prefix.$error.': '.$errorDescription;
        }
        if (null !== $error && '' !== $error) {
            return $prefix.$error;
        }
        if (null !== $errorDescription && '' !== $errorDescription) {
            return $prefix.$errorDescription;
        }
        if (null !== $previous) {
            return $prefix.$previous->getMessage();
        }

        return 'Token service responded with status '.$statusCode;
    }
}
