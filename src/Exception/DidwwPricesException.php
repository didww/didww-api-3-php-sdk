<?php

namespace Didww\Exception;

/**
 * Thrown when a prices service call fails. Use getKind() to tell the cases
 * apart.
 */
final class DidwwPricesException extends \RuntimeException implements DidwwExceptionInterface
{
    /**
     * @param list<array<string, mixed>> $errors
     */
    public function __construct(
        private readonly PricesErrorKind $kind,
        private readonly ?int $statusCode = null,
        private readonly ?string $errorCode = null,
        private readonly array $errors = [],
        private readonly ?int $retryAfter = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(self::buildMessage($statusCode, $errors, $previous), 0, $previous);
    }

    public function getKind(): PricesErrorKind
    {
        return $this->kind;
    }

    /**
     * The HTTP status of the answer; null when no answer was received.
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * The code of the first error object, for example "price_not_found".
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * The JSON:API error objects of the answer, if any.
     *
     * @return list<array<string, mixed>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * The Retry-After of the answer, when given in seconds.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    private static function buildMessage(?int $statusCode, array $errors, ?\Throwable $previous): string
    {
        $messages = [];
        foreach ($errors as $error) {
            foreach (['detail', 'title'] as $field) {
                if (is_string($error[$field] ?? null) && '' !== $error[$field]) {
                    $messages[] = $error[$field];
                    break;
                }
            }
        }
        if ($messages) {
            return 'Prices service: '.implode('; ', $messages);
        }
        if (null !== $previous) {
            return 'Prices service: '.$previous->getMessage();
        }

        return 'Prices service responded with status '.$statusCode;
    }
}
