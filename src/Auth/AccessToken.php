<?php

namespace Didww\Auth;

/**
 * An access token granted by the token service.
 *
 * toArray() holds every field under its wire name, the access token included,
 * so that a TokenStore can persist it; var_dump(), print_r() and the string
 * form never show the access token.
 */
final readonly class AccessToken
{
    /**
     * @param string[] $audience
     */
    public function __construct(
        #[\SensitiveParameter] private string $accessToken,
        private string $tokenType,
        private int $expiresIn,
        private array $audience,
        private string $scope,
        private string $apiVersion,
        private \DateTimeImmutable $mintedAt,
    ) {
    }

    /**
     * Builds a token from its wire fields (access_token, token_type,
     * expires_in, aud, scope, api_version, minted_at). Missing or mistyped
     * fields become empty values; a missing minted_at becomes now.
     */
    public static function fromArray(#[\SensitiveParameter] array $fields): self
    {
        $audience = $fields['aud'] ?? [];
        if (is_string($audience)) {
            $audience = [$audience];
        }
        $audience = is_array($audience) ? array_values(array_filter($audience, 'is_string')) : [];

        $mintedAt = null;
        if (is_string($fields['minted_at'] ?? null)) {
            try {
                $mintedAt = new \DateTimeImmutable($fields['minted_at']);
            } catch (\Exception) {
                $mintedAt = null;
            }
        }

        return new self(
            self::stringField($fields, 'access_token'),
            self::stringField($fields, 'token_type'),
            is_int($fields['expires_in'] ?? null) || (is_string($fields['expires_in'] ?? null) && ctype_digit($fields['expires_in'])) ? (int) $fields['expires_in'] : 0,
            $audience,
            self::stringField($fields, 'scope'),
            self::stringField($fields, 'api_version'),
            $mintedAt ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }

    /**
     * The token under its wire names, the access token included; minted_at is
     * an ISO 8601 date-time.
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'aud' => $this->audience,
            'scope' => $this->scope,
            'api_version' => $this->apiVersion,
            'minted_at' => $this->mintedAt->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * The bearer token. Treat it like the API key.
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * The lifetime of the token in seconds.
     */
    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    /**
     * The audiences (service hostnames) the token is valid for.
     *
     * @return string[]
     */
    public function getAudience(): array
    {
        return $this->audience;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }

    /**
     * When the SDK received the token.
     */
    public function getMintedAt(): \DateTimeImmutable
    {
        return $this->mintedAt;
    }

    public function __debugInfo(): array
    {
        return ['accessToken' => self::mask($this->accessToken)] + array_slice($this->toArray(), 1);
    }

    public function __toString(): string
    {
        return sprintf(
            'AccessToken{accessToken: %s, tokenType: %s, expiresIn: %d, audience: [%s], scope: %s, apiVersion: %s, mintedAt: %s}',
            self::mask($this->accessToken),
            $this->tokenType,
            $this->expiresIn,
            implode(' ', $this->audience),
            $this->scope,
            $this->apiVersion,
            $this->mintedAt->format(\DateTimeInterface::ATOM),
        );
    }

    private static function mask(string $value): string
    {
        return '' === $value ? 'null' : '[FILTERED]';
    }

    private static function stringField(array $fields, string $name): string
    {
        return is_string($fields[$name] ?? null) ? $fields[$name] : '';
    }
}
