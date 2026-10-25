<?php

namespace Didww;

use Didww\Auth\Scope;

/**
 * The API key, the environment, the API version and the optional token scope.
 *
 * The API key is sent to the token service only; every API request carries a
 * short-lived bearer token minted from it.
 */
class Credentials
{
    /**
     * The API version requested when no other is configured. Earlier versions
     * are not supported by this major version of the SDK.
     */
    public const DEFAULT_API_VERSION = '2026-10-22';

    private const ENDPOINTS = [
        'production' => [
            'api' => 'https://api.didww.com/v3',
            'auth' => 'https://auth.didww.com',
            'voice_in' => 'https://api-vin.didww.com/v3',
            'voice_out' => 'https://api-vout.didww.com/v3',
            'sms' => 'https://api-sms.didww.com/v3',
        ],
        'sandbox' => [
            'api' => 'https://sandbox-api.didww.com/v3',
            'auth' => 'https://auth-sandbox.didww.com',
            'voice_in' => 'https://api-vin-sandbox.didww.com/v3',
            'voice_out' => 'https://api-vout-sandbox.didww.com/v3',
            'sms' => 'https://api-sms-sandbox.didww.com/v3',
        ],
    ];

    private string $apiKey;
    private string $env;
    private string $version;
    private ?Scope $scope;

    /**
     * @param string            $apiKey  the API key; printable ASCII (tab allowed)
     * @param string            $env     "production" or "sandbox"
     * @param string|null       $version a YYYY-MM-DD API version not earlier than DEFAULT_API_VERSION; null means DEFAULT_API_VERSION
     * @param Scope|string|null $scope   "read" or "full"; null requests no scope, so the token service grants full
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(#[\SensitiveParameter] string $apiKey, string $env, ?string $version = null, Scope|string|null $scope = null)
    {
        self::validateApiKey($apiKey);
        if (!isset(self::ENDPOINTS[$env])) {
            throw new \InvalidArgumentException(sprintf('Environment must be "production" or "sandbox", got "%s"; use the URL arguments of Configuration::configure() for other hosts', $env));
        }
        $version ??= self::DEFAULT_API_VERSION;
        self::validateVersion($version);

        $this->apiKey = $apiKey;
        $this->env = $env;
        $this->version = $version;
        $this->scope = self::toScope($scope);
    }

    public function getEnv(): string
    {
        return $this->env;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getScope(): ?Scope
    {
        return $this->scope;
    }

    /**
     * The JSON:API base URL of the environment.
     */
    public function getEndpoint(): string
    {
        return self::ENDPOINTS[$this->env]['api'];
    }

    /**
     * The token service URL of the environment.
     */
    public function getAuthEndpoint(): string
    {
        return self::ENDPOINTS[$this->env]['auth'];
    }

    public function getVoiceInPricesEndpoint(): string
    {
        return self::ENDPOINTS[$this->env]['voice_in'];
    }

    public function getVoiceOutPricesEndpoint(): string
    {
        return self::ENDPOINTS[$this->env]['voice_out'];
    }

    public function getSmsPricesEndpoint(): string
    {
        return self::ENDPOINTS[$this->env]['sms'];
    }

    public function __debugInfo(): array
    {
        return [
            'apiKey' => '[FILTERED]',
            'env' => $this->env,
            'version' => $this->version,
            'scope' => $this->scope?->value,
        ];
    }

    private static function validateApiKey(#[\SensitiveParameter] string $apiKey): void
    {
        if ('' === $apiKey) {
            throw new \InvalidArgumentException('API key is required');
        }
        if (preg_match('/[\x00-\x08\x0A-\x1F\x7F-\xFF]/', $apiKey)) {
            throw new \InvalidArgumentException('API key contains characters not allowed in an HTTP header');
        }
    }

    private static function validateVersion(string $version): void
    {
        if (!preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $version)) {
            throw new \InvalidArgumentException(sprintf('API version must be a YYYY-MM-DD date, got "%s"', $version));
        }
        if (strcmp($version, self::DEFAULT_API_VERSION) < 0) {
            throw new \InvalidArgumentException(sprintf('API version %s is not supported: this SDK requires %s or later; use didww/didww-api-3-php-sdk 10.x (branch release-10) for 2026-04-16', $version, self::DEFAULT_API_VERSION));
        }
    }

    private static function toScope(Scope|string|null $scope): ?Scope
    {
        if (null === $scope || $scope instanceof Scope) {
            return $scope;
        }

        return Scope::tryFrom($scope) ?? throw new \InvalidArgumentException(sprintf('Scope must be "read" or "full", got "%s"', $scope));
    }
}
