<?php

namespace Didww\Auth;

use Didww\Credentials;
use Didww\Exception\AuthErrorKind;
use Didww\Exception\DidwwAuthException;
use Didww\Internal\GuzzleExceptions;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Mints access tokens per audience with POST {authUrl}/token and caches them
 * in the token store.
 *
 * 5xx answers and transport failures other than TLS errors are retried up to
 * MAX_MINT_ATTEMPTS times with a full-jitter backoff. A 429 answer blocks
 * further mints of the same auth URL and API key, in this process, until its
 * Retry-After time plus a small random delay has passed.
 *
 * @internal
 */
final class TokenProvider
{
    public const CACHE_KEY_PREFIX = 'didww:token:v1:';
    public const MAX_MINT_ATTEMPTS = 4;
    public const MINT_TIMEOUT = 10;
    public const MAX_ERROR_BODY_SIZE = 1048576;
    private const BACKOFF_BASE = 0.5;
    private const BACKOFF_CAP = 8;
    private const STORE_READ_FAILED = 'DIDWW token store read failed';

    // curl errors of TLS and certificates, which retrying cannot fix.
    private const TLS_ERRNOS = [35, 51, 53, 54, 58, 59, 60, 64, 66, 77, 80, 82, 83, 90, 91, 98];

    /** @var array<string, float> 429 windows per auth URL and API key, in clock seconds */
    private static array $blockedUntil = [];

    private string $authUrl;
    private string $apiKeyDigest;

    /** @var callable(float): void */
    private $sleep;
    /** @var callable(): float */
    private $random;
    /** @var callable(): float */
    private $clock;

    /**
     * @param (callable(float): void)|null $sleep  waits the given seconds (@internal, for tests)
     * @param (callable(): float)|null     $random returns a number in [0, 1) (@internal, for tests)
     * @param (callable(): float)|null     $clock  monotonic seconds (@internal, for tests)
     */
    public function __construct(
        private readonly ClientInterface $http,
        string $authUrl,
        private readonly Credentials $credentials,
        private readonly TokenStore $store,
        private readonly LoggerInterface $logger,
        ?callable $sleep = null,
        ?callable $random = null,
        ?callable $clock = null,
    ) {
        $this->authUrl = rtrim($authUrl, '/');
        $this->apiKeyDigest = hash('sha256', $credentials->getApiKey());
        $this->sleep = $sleep ?? static function (float $seconds): void {
            if ($seconds > 0) {
                usleep((int) round($seconds * 1e6));
            }
        };
        $this->random = $random ?? static fn (): float => mt_rand() / (mt_getrandmax() + 1);
        $this->clock = $clock ?? static fn (): float => hrtime(true) / 1e9;
    }

    /**
     * Returns the cached token for $audience or mints one.
     *
     * @throws DidwwAuthException
     */
    public function token(string $audience): AccessToken
    {
        $key = $this->cacheKey($audience);
        try {
            $token = $this->store->get($key);
            if (null !== $token && '' !== $token->getAccessToken()) {
                return $token;
            }
        } catch (\Throwable $e) {
            $this->warning(self::STORE_READ_FAILED, $e);
        }

        $token = $this->mint($audience);
        try {
            $this->store->set($key, $token, $token->getExpiresIn());
        } catch (\Throwable $e) {
            $this->warning('DIDWW token store write failed', $e);
        }

        return $token;
    }

    /**
     * Evicts the cached token for $audience only while it is still the refused
     * one, so that a token minted meanwhile by another caller survives.
     */
    public function invalidate(string $audience, #[\SensitiveParameter] string $refusedAccessToken): void
    {
        $key = $this->cacheKey($audience);
        try {
            $token = $this->store->get($key);
        } catch (\Throwable $e) {
            $this->warning(self::STORE_READ_FAILED, $e);

            return;
        }
        if (null === $token || !hash_equals($token->getAccessToken(), $refusedAccessToken)) {
            return;
        }
        try {
            $this->store->delete($key);
        } catch (\Throwable $e) {
            $this->warning('DIDWW token store delete failed', $e);
        }
    }

    public function cacheKey(string $audience): string
    {
        return self::CACHE_KEY_PREFIX.hash('sha256', implode("\n", [
            $this->authUrl,
            $this->apiKeyDigest,
            $audience,
            $this->credentials->getVersion(),
            $this->credentials->getScope()?->value ?? '-',
        ]));
    }

    /**
     * Forgets every 429 window of the process (tests).
     */
    public static function forgetRateLimits(): void
    {
        self::$blockedUntil = [];
    }

    private function mint(string $audience): AccessToken
    {
        $this->throwIfRateLimited();

        $body = ['aud' => [$audience], 'api_version' => $this->credentials->getVersion()];
        if (null !== $this->credentials->getScope()) {
            $body['scope'] = $this->credentials->getScope()->value;
        }
        $start = ($this->clock)();
        $response = $this->post(json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        $token = $this->tokenFrom($response, $audience);
        $this->log('debug', 'DIDWW token minted', [
            'aud' => $token->getAudience(),
            'scope' => $token->getScope(),
            'api_version' => $token->getApiVersion(),
            'expires_in' => $token->getExpiresIn(),
            'duration_ms' => (int) round((($this->clock)() - $start) * 1000),
        ]);

        return $token;
    }

    /**
     * @return array{int, string, string} status, Retry-After header and at most MAX_ERROR_BODY_SIZE bytes of the body
     */
    private function post(string $body): array
    {
        for ($attempt = 1;; ++$attempt) {
            $failure = null;
            $response = null;
            try {
                $response = $this->attempt($body);
            } catch (TransferException $e) {
                $failure = $e;
            } catch (DidwwAuthException $e) {
                throw $e;
            } catch (\Throwable $e) {
                // Its trace holds the request options with the API key, so it
                // is not chained.
                throw new DidwwAuthException(AuthErrorKind::CONNECTION_ERROR, previous: new \RuntimeException($e::class.': '.$e->getMessage()));
            }

            if (null !== $response && ($response[0] < 500 || self::MAX_MINT_ATTEMPTS === $attempt)) {
                return $response;
            }
            if (null !== $failure && (self::isTlsError($failure) || self::MAX_MINT_ATTEMPTS === $attempt)) {
                throw new DidwwAuthException(AuthErrorKind::CONNECTION_ERROR, previous: GuzzleExceptions::withoutHeader($failure, 'Api-Key', withoutBody: true));
            }

            ($this->sleep)(($this->random)() * min(self::BACKOFF_CAP, self::BACKOFF_BASE * 2 ** $attempt));
            $this->throwIfRateLimited();
        }
    }

    /**
     * @return array{int, string, string}
     */
    private function attempt(string $body): array
    {
        /** @var ResponseInterface $response */
        $response = $this->http->request('POST', $this->authUrl.'/token', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Api-Key' => $this->credentials->getApiKey(),
            ],
            'body' => $body,
            'timeout' => self::MINT_TIMEOUT,
            'connect_timeout' => self::MINT_TIMEOUT,
            'allow_redirects' => false,
            'http_errors' => false,
            'decode_content' => false,
            'cookies' => false,
            'stream' => false,
        ]);

        return [
            $response->getStatusCode(),
            $response->getHeaderLine('Retry-After'),
            Utils::copyToString($response->getBody(), self::MAX_ERROR_BODY_SIZE),
        ];
    }

    /**
     * @param array{int, string, string} $response
     */
    private function tokenFrom(#[\SensitiveParameter] array $response, string $audience): AccessToken
    {
        [$status, $retryAfterHeader, $body] = $response;
        $fields = json_decode($body, true);
        if (!is_array($fields)) {
            $fields = [];
        }
        $error = is_string($fields['error'] ?? null) ? $fields['error'] : null;
        $description = is_string($fields['error_description'] ?? null) ? $fields['error_description'] : null;

        if (200 === $status) {
            unset($fields['minted_at']);
            $token = AccessToken::fromArray($fields);
            $this->verifyGrant($token, $audience);

            return $token;
        }
        if (429 === $status) {
            $retryAfter = self::retryAfterSeconds($retryAfterHeader);
            self::$blockedUntil[$this->rateLimitKey()] = ($this->clock)() + $retryAfter + ($this->random)() * max(1, $retryAfter / 5);

            throw new DidwwAuthException(AuthErrorKind::RATE_LIMITED, $status, $error, $description, $retryAfter);
        }
        $kind = match (true) {
            400 === $status => AuthErrorKind::INVALID_REQUEST,
            401 === $status => AuthErrorKind::INVALID_CLIENT,
            $status >= 500 && $status <= 599 => AuthErrorKind::SERVER_ERROR,
            default => AuthErrorKind::UNEXPECTED_STATUS,
        };

        throw new DidwwAuthException($kind, $status, $error, $description);
    }

    private function verifyGrant(#[\SensitiveParameter] AccessToken $token, string $audience): void
    {
        if ('' === $token->getAccessToken() || !in_array($audience, $token->getAudience(), true)) {
            throw new DidwwAuthException(AuthErrorKind::INVALID_GRANT, 200, errorDescription: sprintf('granted audience [%s], requested %s', implode(' ', $token->getAudience()), $audience));
        }
        $scope = $this->credentials->getScope()?->value;
        if (null !== $scope && $token->getScope() !== $scope) {
            $this->log('warning', sprintf('DIDWW token scope mismatch: requested %s, granted %s', $scope, $token->getScope()), ['requested' => $scope, 'granted' => $token->getScope()]);
        }
        $version = $this->credentials->getVersion();
        if ($token->getApiVersion() !== $version) {
            $this->log('warning', sprintf('DIDWW token api_version mismatch: requested %s, granted %s', $version, $token->getApiVersion()), ['requested' => $version, 'granted' => $token->getApiVersion()]);
        }
    }

    // Throws RATE_LIMITED while a 429 window of the token service is open, so
    // that no request is sent inside it.
    private function throwIfRateLimited(): void
    {
        $until = self::$blockedUntil[$this->rateLimitKey()] ?? null;
        if (null === $until) {
            return;
        }
        $remaining = $until - ($this->clock)();
        if ($remaining <= 0) {
            return;
        }

        throw new DidwwAuthException(AuthErrorKind::RATE_LIMITED, 429, 'rate_limit_exceeded', retryAfter: (int) ceil($remaining));
    }

    private function rateLimitKey(): string
    {
        return $this->authUrl."\n".$this->apiKeyDigest;
    }

    // The Retry-After seconds of a 429, at least one; an HTTP date or a missing
    // value counts as one second.
    private static function retryAfterSeconds(string $header): int
    {
        if (!preg_match('/\A\s*[0-9]{1,9}\s*\z/', $header)) {
            return 1;
        }

        return max(1, (int) trim($header));
    }

    // A curl error names its code at the start of the message ("cURL error
    // 60: ..."); the message of another handler is matched by its words.
    private static function isTlsError(TransferException $e): bool
    {
        if (1 === preg_match('/\AcURL error (\d+):/', $e->getMessage(), $match)) {
            return in_array((int) $match[1], self::TLS_ERRNOS, true);
        }

        return 1 === preg_match('/\b(SSL|TLS|certificate|crypto)\b/i', $e->getMessage());
    }

    private function warning(string $message, \Throwable $e): void
    {
        $this->log('warning', $message, ['exception' => $e]);
    }

    private function log(string $level, string $message, array $context): void
    {
        try {
            $this->logger->log($level, $message, $context);
        } catch (\Throwable) {
            // A failing logger must not fail the call.
        }
    }
}
