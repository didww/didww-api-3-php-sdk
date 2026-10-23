<?php

namespace Didww;

use Didww\Auth\BearerMiddleware;
use Didww\Auth\MemoryTokenStore;
use Didww\Auth\Scope;
use Didww\Auth\TokenProvider;
use Didww\Auth\TokenStore;
use Didww\Internal\CurlStallLimit;
use Didww\Internal\Url;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Configuration
{
    private static $documentClient;

    private static $credentials;

    private static ?ClientInterface $httpClient = null;

    private static ?ClientInterface $rawHttpClient = null;

    private static ?TokenProvider $tokenProvider = null;

    private static ?TokenStore $tokenStore = null;

    private static ?TokenStore $defaultTokenStore = null;

    private static ?LoggerInterface $logger = null;

    /** @var array<string, string> */
    private static array $urls = [];

    public static function getCredentials()
    {
        return self::$credentials;
    }

    public static function getDocumentClient()
    {
        return self::$documentClient;
    }

    /**
     * Configures the SDK. Every API request carries a bearer token minted from
     * the API key by the token service; the API key is sent to the token
     * service only.
     *
     * The Guzzle configuration is used as given on top of the SDK defaults:
     * connect_timeout 10, read_timeout 60, a curl stall timeout (under 1 byte/s
     * for 60 s; not sent to Guzzle's stream handler, which rejects curl
     * options), no total timeout (a "timeout" applies to file downloads too),
     * no redirects, no decompression and no cookies. Redirects are never
     * followed on authenticated requests.
     *
     * @param array                $httpClientConfig  Guzzle client configuration; a "handler" is wrapped by the SDK
     * @param string|null          $apiVersion        overrides the API version of $credentials
     * @param Scope|string|null    $scope             overrides the scope of $credentials
     * @param TokenStore|null      $tokenStore        where tokens are cached; default: an in-memory store shared by the process
     * @param LoggerInterface|null $logger            receives token warnings and debug messages; default: none
     * @param string|null          $baseUrl           JSON:API base URL (with or without /v3); default: the environment's
     * @param string|null          $authUrl           token service URL; default: the environment's
     * @param string|null          $voiceInPricesUrl  Voice IN prices URL (with or without /v3); default: the environment's
     * @param string|null          $voiceOutPricesUrl Voice OUT prices URL (with or without /v3); default: the environment's
     * @param string|null          $smsPricesUrl      SMS prices URL (with or without /v3); default: the environment's
     *
     * @throws \InvalidArgumentException when a value is not valid
     */
    public static function configure(
        Credentials $credentials,
        array $httpClientConfig = [],
        ?string $apiVersion = null,
        Scope|string|null $scope = null,
        ?TokenStore $tokenStore = null,
        ?LoggerInterface $logger = null,
        ?string $baseUrl = null,
        ?string $authUrl = null,
        ?string $voiceInPricesUrl = null,
        ?string $voiceOutPricesUrl = null,
        ?string $smsPricesUrl = null,
    ): void {
        if (null !== $apiVersion || null !== $scope) {
            $credentials = new Credentials($credentials->getApiKey(), $credentials->getEnv(), $apiVersion ?? $credentials->getVersion(), $scope ?? $credentials->getScope());
        }

        $urls = [
            'API base' => $baseUrl ?? $credentials->getEndpoint(),
            'token service' => $authUrl ?? $credentials->getAuthEndpoint(),
            'Voice IN prices' => $voiceInPricesUrl ?? $credentials->getVoiceInPricesEndpoint(),
            'Voice OUT prices' => $voiceOutPricesUrl ?? $credentials->getVoiceOutPricesEndpoint(),
            'SMS prices' => $smsPricesUrl ?? $credentials->getSmsPricesEndpoint(),
        ];
        foreach ($urls as $name => $url) {
            if (!Url::isHttpUrl($url)) {
                throw new \InvalidArgumentException(sprintf('%s URL must be an http or https URL without a query or fragment, got "%s"', $name, $url));
            }
        }
        $baseUri = Url::withV3($urls['API base']);

        $logger ??= new NullLogger();
        $tokenStore ??= self::$defaultTokenStore ??= new MemoryTokenStore();

        $config = self::httpClientConfig($httpClientConfig);
        if (isset($config['handler'])) {
            $inner = $config['handler'];
        } else {
            // Guzzle builds the handler, so that its client-only options
            // (connection caps, transport sharing) still apply.
            $inner = (new \GuzzleHttp\Client($config))->getConfig('handler');
            unset($config['max_host_connections'], $config['max_total_connections'], $config['transport_sharing']);
        }
        $inner = CurlStallLimit::wrap($inner);
        $rawHttpClient = new \GuzzleHttp\Client(['handler' => $inner, 'allow_redirects' => false] + $config);
        $tokenProvider = new TokenProvider($rawHttpClient, $urls['token service'], $credentials, $tokenStore, $logger);
        $config['handler'] = (new BearerMiddleware($tokenProvider, (string) parse_url($baseUri, PHP_URL_PATH)))($inner);
        $httpClient = new \GuzzleHttp\Client($config);

        $client = new Client($httpClient);
        $client->setBaseUri($baseUri);

        $typeMapper = new TypeMapper();
        $typeMapper->registerItems();

        $responseParser = \Swis\JsonApi\Client\Parsers\ResponseParser::create($typeMapper);

        self::$credentials = $credentials;
        self::$httpClient = $httpClient;
        self::$rawHttpClient = $rawHttpClient;
        self::$tokenProvider = $tokenProvider;
        self::$tokenStore = $tokenStore;
        self::$logger = $logger;
        self::$urls = [
            'auth' => $urls['token service'],
            'voice_in' => $urls['Voice IN prices'],
            'voice_out' => $urls['Voice OUT prices'],
            'sms' => $urls['SMS prices'],
        ];
        self::$documentClient = new \Swis\JsonApi\Client\DocumentClient($client, $responseParser);
    }

    public static function getApiKey(): string
    {
        return self::$credentials->getApiKey();
    }

    /**
     * The JSON:API base URL, ending with /v3.
     */
    public static function getBaseUri(): string
    {
        return self::getDocumentClient()->getBaseUri();
    }

    /**
     * The Guzzle client of the SDK: it adds the bearer token of the
     * BearerMiddleware::AUDIENCE_OPTION request option (Audience::API by
     * default, none for public_keys, false for none) and never follows
     * redirects. After a 401 it mints a new token and sends the request once
     * more only when the body is empty or seekable and the sink is none, a
     * path, a seekable resource written at its end, a seekable stream written
     * at its end that the 401 was not written to, or another stream the 401
     * was not written to; otherwise the 401 is returned.
     */
    public static function getHttpClient(): ClientInterface
    {
        return self::$httpClient;
    }

    /**
     * The Guzzle client of the SDK without authentication, sharing the
     * configuration and handler of getHttpClient(); it never follows
     * redirects.
     *
     * @internal
     */
    public static function getRawHttpClient(): ClientInterface
    {
        return self::$rawHttpClient;
    }

    /**
     * @internal
     */
    public static function getTokenProvider(): TokenProvider
    {
        return self::$tokenProvider;
    }

    public static function getTokenStore(): TokenStore
    {
        return self::$tokenStore;
    }

    public static function getLogger(): LoggerInterface
    {
        return self::$logger;
    }

    public static function getAuthUri(): string
    {
        return self::$urls['auth'];
    }

    public static function getVoiceInPricesUri(): string
    {
        return self::$urls['voice_in'];
    }

    public static function getVoiceOutPricesUri(): string
    {
        return self::$urls['voice_out'];
    }

    public static function getSmsPricesUri(): string
    {
        return self::$urls['sms'];
    }

    private static function userAgent(): string
    {
        return 'didww-php-sdk/'.Client::sdkVersion();
    }

    // The user configuration on top of the SDK defaults. The curl stall limit
    // is added per request by CurlStallLimit, as Guzzle's stream handler
    // rejects curl options.
    private static function httpClientConfig(array $user): array
    {
        $defaults = [
            'connect_timeout' => 10,
            'read_timeout' => 60,
            'allow_redirects' => false,
            'decode_content' => false,
            'cookies' => false,
        ];
        $config = $user + $defaults;

        $headers = [];
        $hasUserAgent = false;
        foreach ($user['headers'] ?? [] as $name => $value) {
            $lower = strtolower((string) $name);
            if ('if-none-match' === $lower) {
                continue;
            }
            $hasUserAgent = $hasUserAgent || 'user-agent' === $lower;
            $headers[$name] = $value;
        }
        if (!$hasUserAgent) {
            $headers['User-Agent'] = self::userAgent();
        }
        $config['headers'] = $headers;

        return $config;
    }
}
