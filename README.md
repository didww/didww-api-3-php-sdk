PHP client for DIDWW API v3.

[![PHP from Packagist](https://img.shields.io/packagist/php-v/didww/didww-api-3-php-sdk.svg)](https://packagist.org/packages/didww/didww-api-3-php-sdk)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/didww/didww-api-3-php-sdk.svg)](https://packagist.org/packages/didww/didww-api-3-php-sdk)
![Tests](https://github.com/didww/didww-api-3-php-sdk/workflows/Tests/badge.svg)
![Coverage](https://img.shields.io/endpoint?url=https://didww.github.io/didww-api-3-php-sdk/badge.json)

## About DIDWW API v3

The DIDWW API provides a simple yet powerful interface that allows you to fully integrate your own applications with DIDWW services. An extensive set of actions may be performed using this API, such as ordering and configuring phone numbers, setting capacity, creating SIP trunks and retrieving CDRs and other operational data.

The DIDWW API v3 is a fully compliant implementation of the [JSON API specification](http://jsonapi.org/format/).

This SDK uses [swisnl/json-api-client](https://github.com/swisnl/json-api-client) for JSON:API serialization and deserialization.

Read more https://doc.didww.com/api

## API Version

This SDK targets DIDWW API version `2026-10-22` (`Didww\Credentials::DEFAULT_API_VERSION`). The version is not sent as a header: it is requested in the `api_version` of every access token the SDK mints (see [Authentication](#authentication)).

| Package Version | Branch | DIDWW API Version |
|-----------------|--------|-------------------|
| **11.x** | [`master`](https://github.com/didww/didww-api-3-php-sdk) | [`2026-10-22`](https://doc.didww.com/api3/2026-10-22/index.html) |
| **10.x** | [`release-10`](https://github.com/didww/didww-api-3-php-sdk/tree/release-10) | [`2026-04-16`](https://doc.didww.com/api3/2026-04-16/index.html) |
| **9.x** | [`2022-05-10`](https://github.com/didww/didww-api-3-php-sdk/tree/2022-05-10) | [`2022-05-10`](https://doc.didww.com/api3/2022-05-10/index.html) |

A later version can be requested through `Credentials` or the `apiVersion` argument of `Configuration::configure()`. A value that is not a `YYYY-MM-DD` date, or a version earlier than `2026-10-22`, throws an `\InvalidArgumentException`. The resources of earlier API versions are not in this package: use 10.x from the [`release-10`](https://github.com/didww/didww-api-3-php-sdk/tree/release-10) branch for API `2026-04-16` (see [Migrating from 10.x](#migrating-from-10x)).

## Requirements

- PHP 8.2+
- [Guzzle](https://github.com/guzzle/guzzle) 8 (`guzzlehttp/guzzle` `^8.0.1`)
- Extensions: `ext-curl`, `ext-json`, `ext-openssl`, `ext-zlib`
- Optional: [`psr/simple-cache`](https://packagist.org/packages/psr/simple-cache) and a PSR-16 cache implementation, to share access tokens between processes (see [Token cache](#token-cache))

## Installation

```sh
composer require didww/didww-api-3-php-sdk:^11
```

For API `2026-04-16`, stay on 10.x: `composer require didww/didww-api-3-php-sdk:^10.2`.

## Usage

```php
$credentials = new Didww\Credentials('YOUR_API_KEY', 'sandbox');
Didww\Configuration::configure($credentials);

// Check balance
$balance = Didww\Item\Balance::find()->getData();
echo "Balance: " . $balance->getTotalBalance();

// List DID groups with stock keeping units
$didGroups = Didww\Item\DidGroup::all([
    'include' => 'stock_keeping_units',
    'filter' => ['area_name' => 'Acapulco'],
])->getData();
```

For more examples visit [examples/](examples/).

For details on obtaining your API key please visit https://doc.didww.com/api3/configuration.html

## Examples

- Source code: [examples/](examples/)
- How to run: [examples/README.md](examples/README.md)

## Configuration

```php
use Didww\Auth\Scope;

// Production
$credentials = new Didww\Credentials('YOUR_API_KEY', 'production');
Didww\Configuration::configure($credentials);

// Sandbox, read-only tokens, a PSR-3 logger and a proxy
$credentials = new Didww\Credentials('YOUR_API_KEY', 'sandbox');
Didww\Configuration::configure(
    $credentials,
    ['proxy' => 'http://proxy.example.com:8080'],
    scope: Scope::READ,
    logger: $logger, // any Psr\Log\LoggerInterface, e.g. Monolog
);
```

`Credentials` takes the API key, the environment and, optionally, the API version and the scope: `new Didww\Credentials($apiKey, $env, $version = null, $scope = null)`. `Configuration::configure()` takes the credentials, the Guzzle configuration and the named arguments below. The configuration is static: the last `configure()` call applies to the whole process.

| Argument | Default | Description |
|----------|---------|-------------|
| `$credentials` | required | `new Didww\Credentials($apiKey, 'production')` or `'sandbox'`. The environment selects the API, token service and price hosts. See [Environments](#environments). |
| `$httpClientConfig` | `[]` | [Guzzle configuration](https://docs.guzzlephp.org/en/stable/request-options.html), used on top of the SDK defaults. See [HTTP client](#http-client-proxy-tls-timeouts). |
| `apiVersion:` | the version of `$credentials` (`Credentials::DEFAULT_API_VERSION`) | API version requested in every token. See [API Version](#api-version). |
| `scope:` | the scope of `$credentials` (none) | `Didww\Auth\Scope::READ`, `Scope::FULL`, `'read'` or `'full'`. Without a scope none is requested and the token service grants `full`. See [Scope](#scope). |
| `tokenStore:` | an in-memory store shared by the process | A `Didww\Auth\TokenStore`. See [Token cache](#token-cache). |
| `logger:` | `Psr\Log\NullLogger` | PSR-3 logger for the token warnings and debug messages. See [Logging](#logging). |
| `baseUrl:` | API URL of the environment | JSON:API base URL, with or without `/v3`. |
| `authUrl:` | Token service of the environment | Token service base URL. |
| `voiceInPricesUrl:`, `voiceOutPricesUrl:`, `smsPricesUrl:` | Price hosts of the environment | Price service base URLs, with or without `/v3`. |

The API key is sent only to the token service. An overridden URL, for example to send the traffic through a proxy, does not change the token audiences (see [Authentication](#authentication)).

### Validation

`new Credentials(...)` and `Configuration::configure()` throw an `\InvalidArgumentException`, before any request, for:

- an empty API key, or one with a character that cannot be sent in an HTTP header (a control character other than tab, such as a trailing newline, or a byte outside ASCII, such as a non-breaking space);
- an environment other than `production` and `sandbox` (use the URL arguments of `configure()` for other hosts);
- an API version that is not a `YYYY-MM-DD` date, or that is earlier than `2026-10-22`;
- a scope other than `read` and `full`;
- a base, token service or prices URL that is not an http or https URL with a host, or that has a query or fragment.

### Environments

| Environment | API | Token service | Voice IN prices | Voice OUT prices | SMS prices |
|-------------|-----|---------------|-----------------|------------------|------------|
| `production` | `https://api.didww.com/v3` | `https://auth.didww.com` | `https://api-vin.didww.com/v3` | `https://api-vout.didww.com/v3` | `https://api-sms.didww.com/v3` |
| `sandbox` | `https://sandbox-api.didww.com/v3` | `https://auth-sandbox.didww.com` | `https://api-vin-sandbox.didww.com/v3` | `https://api-vout-sandbox.didww.com/v3` | `https://api-sms-sandbox.didww.com/v3` |

A firewall that limits outgoing traffic must allow the token service, and the price hosts when you use [Prices](#prices).

### HTTP client (proxy, TLS, timeouts)

Every call goes through Guzzle, built from `$httpClientConfig` on top of these defaults. The one exception is `SupportingDocumentTemplate::download()`, which fetches the public template file with plain curl, without this configuration, the token or the API key, and follows redirects.

| Option | Default | Meaning |
|--------|---------|---------|
| `connect_timeout` | `10` | Seconds to connect. |
| `read_timeout` | `60` | Seconds the connection may sit silent (connecting, waiting for the response, reading the body), applied only by Guzzle's stream handler. With the default curl handler a stalled transfer is ended by the stall limit. |
| curl stall limit | 1 byte/s over 60 s | A transfer that receives less than 1 byte/s for 60 s, the wait for the response included, is aborted (`CURLOPT_LOW_SPEED_LIMIT` / `CURLOPT_LOW_SPEED_TIME`). Not sent to Guzzle's stream handler, which rejects curl options; `read_timeout` covers a stall there. |
| `timeout` | none | No total timeout, so a large price or export file is not cut off. |
| `allow_redirects` | `false` | Redirects are never followed. |
| `decode_content` | `false` | Responses are not decompressed; price and export files are written as served. |
| `cookies` | `false` | No cookies are stored or sent. |

Your keys replace the defaults. `curl` options are merged with the stall limit (your `CURLOPT_LOW_SPEED_*` win); Guzzle's stream handler rejects any `curl` options you pass. `headers` are kept, except `If-None-Match`, and a `User-Agent` is added unless you set one. An `on_headers` or `on_stats` callback you pass also runs for export and price file downloads, after the SDK's own. A `handler` you pass is wrapped by the SDK, so its middleware runs for every call: the token service, the JSON:API, export download, encrypted file upload and prices. That middleware sees the `Authorization` header, and the `Api-Key` header of token requests: do not log them.

```php
Didww\Configuration::configure($credentials, [
    'proxy' => 'http://proxy.example.com:8080',
    'verify' => '/etc/ssl/certs/ca-certificates.crt',
    'connect_timeout' => 5,
]);
```

- **Redirects.** Requests made through the SDK's Guzzle client are never redirected, whatever `allow_redirects` says, so that a token or the API key is not sent to another URL.
- **Total timeout.** A `timeout` you set also bounds the download of a large price or export file. Prefer `connect_timeout` and the stall limit (`CURLOPT_LOW_SPEED_TIME`). A price lookup takes at most 60 s, or your `timeout` when it is shorter.
- **Token requests** use the same handler and options, with a 10 s connect and total timeout per attempt.

`Configuration::getHttpClient()` returns the Guzzle client of the SDK, which adds the bearer token to every request except those to `public_keys` (pass the `BearerMiddleware::AUDIENCE_OPTION` request option as `false` for none). After a 401 it mints a new token and sends the request once more only when the body is empty or seekable and the `sink` is none, a path, a seekable resource written at its end (cut back to that size), a seekable stream written at its end that the 401 was not written to, or another stream the 401 was not written to; otherwise the 401 is returned.

## Authentication

Every request carries `Authorization: Bearer <token>`. The SDK mints the tokens itself with `POST /token` on the token service, sending your API key in the `Api-Key` header. The API key is sent nowhere else. No code change is needed for this: `new Didww\Credentials('YOUR_API_KEY', 'production')` works as before.

- **Audience.** One token per audience, minted on the first call to a service and reused until it expires. The audience is the production hostname of the service, in the sandbox too:

  | Calls | Token audience |
  |-------|----------------|
  | JSON:API resources, export download, encrypted file upload | `api.didww.com` |
  | Voice IN prices | `api-vin.didww.com` |
  | Voice OUT prices | `api-vout.didww.com` |
  | SMS OUT (A2P, P2P, OTP) and SMS IN prices | `api-sms.didww.com` |

  `Didww\Item\PublicKey::all()` is sent without a token.
- **Expiry and 401.** A cached token lives for the `expires_in` granted; the SDK adds no local expiry margin. When a request is answered 401, the SDK drops that token, mints a new one and sends the request once more. A second 401 drops the new token too and becomes the result of the call (see [Error Handling](#error-handling)). A 403, such as `insufficient_scope`, is never retried.
- **Token service errors** throw a `Didww\Exception\DidwwAuthException` from any call that needs a new token (kinds in [Error Handling](#error-handling)):
  - A 429 throws `AuthErrorKind::RATE_LIMITED` with `getRetryAfter()` (the `Retry-After` seconds, at least 1). Every further token request for the same token service and API key in the process (under PHP-FPM or mod_php: in the same web request) fails the same way without a request until that time has passed, plus a small random delay so that clients do not retry in step. Such a refusal reports the seconds still left, so retry in a loop rather than once.
  - A 5xx answer, a connection failure or a timeout is retried with exponential backoff and full jitter (0.5 s base, 8 s cap, 4 attempts in all), then throws `AuthErrorKind::SERVER_ERROR` or `AuthErrorKind::CONNECTION_ERROR`.
  - A 400, a 401 or a TLS failure is thrown at once, without a retry.

### Scope

```php
use Didww\Auth\Scope;

new Didww\Credentials('YOUR_API_KEY', 'production', scope: Scope::READ); // GET and HEAD only
new Didww\Credentials('YOUR_API_KEY', 'production', scope: Scope::FULL);
new Didww\Credentials('YOUR_API_KEY', 'production'); // the default: no scope is requested, the token service grants full

// Or when configuring
Didww\Configuration::configure($credentials, scope: Scope::READ);
```

A `read` token is enough for the price lookups and price files. A write with a `read` token is answered 403, which the JSON:API resources return as document errors (see [Error Handling](#error-handling)).

The SDK compares each token it receives with what it asked for:

- a different `scope` (for example `full` granted for a `read` request) or a different `api_version` is logged as a warning (see [Logging](#logging)), and the token is used;
- a token with an empty access token, or whose `aud` does not include the requested audience, throws `AuthErrorKind::INVALID_GRANT` and is not cached.

### Token cache

Tokens are cached in a `Didww\Auth\TokenStore`, keyed by the token service URL, the API key (as a SHA-256 hash), the audience, the API version and the scope. Each entry is stored for the token's `expires_in`.

The default `Didww\Auth\MemoryTokenStore` keeps the tokens in process memory, and every `configure()` call of the process without its own store shares it. A long-running worker (a queue consumer, a daemon, a CLI job) therefore reuses its tokens. **Under PHP-FPM or mod_php the process memory lasts one request, so every request would mint new tokens:** pass a store that outlives the request, such as `Didww\Auth\Psr16TokenStore` over a [PSR-16](https://www.php-fig.org/psr/psr-16/) cache (requires `psr/simple-cache`):

```php
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

// One server: APCu is shared by the PHP-FPM workers
$cache = new Psr16Cache(new ApcuAdapter('didww'));

// Several servers: Redis, with short timeouts
$cache = new Psr16Cache(new RedisAdapter(
    RedisAdapter::createConnection('redis://localhost', ['timeout' => 2, 'read_timeout' => 2]),
));

Didww\Configuration::configure($credentials, tokenStore: new Didww\Auth\Psr16TokenStore($cache));
```

`Psr16TokenStore` stores tokens as arrays under keys that fit the PSR-16 key rules. To use another storage, implement the interface:

```php
interface TokenStore
{
    public function get(string $key): ?AccessToken;
    public function set(string $key, AccessToken $token, int $ttl): void; // a ttl of 0 or less means no expiry
    public function delete(string $key): void;
}
```

`AccessToken::toArray()` and `AccessToken::fromArray()` hold every field under its wire name, the access token included, so that a store can persist it; `var_dump()`, `print_r()` and the string form of an `AccessToken` never show the access token. A shared store holds live bearer tokens, so protect it like the API key. Processes that share a store can each mint once when a token is missing. A store that throws does not fail the call: the error is logged as a warning, and the call goes on with a newly minted token. The SDK cannot interrupt a store call that hangs, so bound the store's own I/O (as with the Redis timeouts above).

### Logging

The `logger:` argument takes any [PSR-3](https://www.php-fig.org/psr/psr-3/) `Psr\Log\LoggerInterface`. Following the PHP library convention, the default is `Psr\Log\NullLogger`: nothing is logged, not even the mismatch warnings, until you pass a logger.

- Warnings: `DIDWW token scope mismatch` and `DIDWW token api_version mismatch` (with `requested` and `granted` in the context), and token store failures (`DIDWW token store read failed`, `write failed`, `delete failed`).
- Debug: `DIDWW token minted`, with `aud`, `scope`, `api_version`, `expires_in` and `duration_ms`.

Neither the token nor the API key is ever logged. A logger that throws does not fail the call.

### Security

The SDK keeps the API key and the tokens out of the exceptions it throws (their messages, requests and chained exceptions) and marks its own parameters that carry them `#[\SensitiveParameter]`, which PHP 8.2+ hides in stack traces. Other stack frames of an HTTP call (in Guzzle, the JSON:API client or your middleware) can hold the request objects, which carry the `Authorization` header or the `Api-Key` header of a token request. Run production with `zend.exception_ignore_args = On` (the `php.ini-production` value), so that exception traces keep no arguments, and do not log or report traces with arguments.

### SDK design decisions

- **Audience strategy.** One token per audience, minted on first use, rather than one token naming every service. An account that never calls the prices services never requests their audiences, so a refused audience cannot break DID management, and every token is valid for one service only.
- **Cache store.** In-process memory by default, shared by the whole process, because the SDK configuration is static. It serves long-running processes; web workloads under PHP-FPM opt into a shared store through `tokenStore:`. Lifetime is decided by the server through the 401 refresh, not by a local expiry margin.
- **Logger.** PSR-3, silent by default (`NullLogger`), as PHP libraries usually are.
- **HTTP defaults.** Connect and read timeouts and a stall limit instead of a total timeout, so that large files are not cut off; no redirects, so that credentials stay on the configured hosts; no decompression, so that files are written as served; no cookies, so that no state is carried between calls.
- **Gzip handling.** Price files and export files are written to your path or stream exactly as served, still gzipped, without being held in memory. The SDK never decompresses a price file on its own: `Didww\Prices\PriceFileReader` reads a downloaded file when you want rows, and `Export::downloadAndDecompress()` writes an export as plain CSV.
- **Upload retry.** The 401 retry sends the request body a second time. `EncryptedFile::upload()` takes the encrypted content as a string and builds the multipart body in memory, so the retry always resends the complete body.
- **Blocking waits.** PHP calls are synchronous: the token backoff and the price file 503 retry wait in the calling process. Run price file downloads from the command line or a scheduled job (cron), not within a web request.

## Resources

### Read-Only Resources

```php
use Didww\Enum\NumberType;

// Countries
$countries = Didww\Item\Country::all()->getData();
$country = Didww\Item\Country::find('uuid')->getData();

// Regions
$regions = Didww\Item\Region::all()->getData();

// Cities
$cities = Didww\Item\City::all()->getData();

// Areas
$areas = Didww\Item\Area::all()->getData();

// NANPA Prefixes
$prefixes = Didww\Item\NanpaPrefix::all()->getData();

// DID Groups (with stock keeping units), filtered by number type
$groups = Didww\Item\DidGroup::all([
    'include' => 'stock_keeping_units',
    'filter' => ['number_type' => NumberType::TOLL_FREE->value],
])->getData();

// Available DIDs
$available = Didww\Item\AvailableDid::all([
    'include' => 'did_group.stock_keeping_units',
])->getData();

// Proof Types
$proofTypes = Didww\Item\ProofType::all()->getData();

// Public Keys
$publicKeys = Didww\Item\PublicKey::all()->getData();

// Address Requirements
$requirements = Didww\Item\AddressRequirement::all()->getData();

// Balance (singleton)
$balance = Didww\Item\Balance::find()->getData();
```

`DidGroup`, `AddressRequirement`, `EmergencyRequirement` and `EmergencyCallingService` have `getNumberType()`, which returns a `Didww\Enum\NumberType` (`LOCAL`, `NATIONAL`, `MOBILE`, `TOLL_FREE`, `SHARED_COST`, `GLOBAL`), or the raw string for a value this SDK does not know. `DidGroup::getIsDemo()` flags demo groups.

### DIDs

```php
// List DIDs
$dids = Didww\Item\Did::all()->getData();

// Update DID - assign trunk and capacity
$did = Didww\Item\Did::find('uuid')->getData();
$did->setDescription('Updated');
$did->setCapacityLimit(20);
$did->setVoiceInTrunk(Didww\Item\VoiceInTrunk::build('trunk-uuid'));
$did->save();
```

#### SMS routing

A DID's inbound messages go to an SMS trunk or to an SMS trunk group. The two are exclusive: `setSmsTrunk()` sends `sms_trunk_group` as `null` in the same request, and `setSmsTrunkGroup()` sends `sms_trunk` as `null`, as `setVoiceInTrunk()` and `setVoiceInTrunkGroup()` do.

```php
// sends sms_trunk and sms_trunk_group: null
$did = Didww\Item\Did::build('did-uuid');
$did->setSmsTrunk(Didww\Item\SmsTrunk::build('sms-trunk-uuid'));
$did->save();

// sends sms_trunk_group and sms_trunk: null
$did = Didww\Item\Did::build('did-uuid');
$did->setSmsTrunkGroup(Didww\Item\SmsTrunkGroup::build('sms-trunk-group-uuid'));
$did->save();

// unroute: sends sms_trunk: null and sms_trunk_group: null
$did = Didww\Item\Did::build('did-uuid');
$did->setSmsTrunk(null);
$did->setSmsTrunkGroup(null);
$did->save();

$dids = Didww\Item\Did::all([
    'filter' => ['sms_trunk.id' => 'sms-trunk-uuid'],
    'include' => 'sms_trunk,sms_trunk_group',
])->getData();
$trunk = $dids[0]->smsTrunk()->getIncluded(); // or ->smsTrunkGroup()->getIncluded()
```

An SMS trunk cannot be deleted while DIDs are routed to it, nor an SMS trunk group while DIDs are routed to it or to one of its trunks: unroute those DIDs, or route them elsewhere, first.

#### Dirty-Only PATCH Serialization

When updating resources, the SDK tracks which attributes and relationships have changed since the resource was loaded (or built). Only dirty fields are included in the PATCH request body, reducing payload size and avoiding unintended overwrites.

```php
// Load a DID from the API - all fields are marked as persisted
$did = Didww\Item\Did::find('uuid')->getData();

// Change only the description - only dirty fields are sent
$did->setDescription('New description');
$did = $did->save()->getData(); // reassign to get synced persisted state
// PATCH body: {"data":{"type":"dids","id":"...","attributes":{"description":"New description"}}}

// Build a resource by ID and set a single field
$did = Didww\Item\Did::build('uuid');
$did->setCapacityLimit(10);
$did = $did->save()->getData();
// PATCH body: {"data":{"type":"dids","id":"...","attributes":{"capacity_limit":10}}}

// Explicitly clear a field by setting it to null
$did = Didww\Item\Did::build('uuid');
$did->setDescription(null);
$did = $did->save()->getData();
// PATCH body: {"data":{"type":"dids","id":"...","attributes":{"description":null}}}

// Included resources also start with clean dirty state
$didDocument = Didww\Item\Did::find('uuid', ['include' => 'voice_in_trunk']);
$trunk = $didDocument->getData()->voiceInTrunk()->getIncluded();
// $trunk has persisted state synced - modifying and saving it sends only dirty fields
$trunk->setName('Renamed trunk');
$trunk = $trunk->save()->getData();
// PATCH body: {"data":{"type":"voice_in_trunks","id":"...","attributes":{"name":"Renamed trunk"}}}
```

### Voice In Trunks

```php
use Didww\Enum\CliFormat;
use Didww\Enum\Codec;
use Didww\Enum\MediaEncryptionMode;
use Didww\Enum\SstRefreshMethod;
use Didww\Enum\TransportProtocol;

// Create SIP trunk
$sip = new Didww\Item\Configuration\Sip([
    'host' => 'sip.example.com',
    'port' => 5060,
    'codec_ids' => Didww\Item\Configuration\Base::getDefaultCodecIds(),
    'transport_protocol_id' => TransportProtocol::UDP,
    'sst_refresh_method_id' => SstRefreshMethod::INVITE,
    'media_encryption_mode' => MediaEncryptionMode::DISABLED,
    'rerouting_disconnect_code_ids' => Didww\Item\Configuration\Base::getDefaultReroutingDisconnectCodeIds(),
]);

$trunk = new Didww\Item\VoiceInTrunk();
$trunk->setName('My SIP Trunk');
$trunk->setCliFormat(CliFormat::E164);
$trunk->setRingingTimeout(30);
$trunk->setConfiguration($sip);

$trunkDocument = $trunk->save();
$trunk = $trunkDocument->getData();

// Update trunk
$trunk->setDescription('Updated');
$trunk->save();

// Delete trunk
$trunk->delete();
```

`pop` is a plain string attribute. `getPop()` returns a `Didww\Enum\Pop` (`NYC`, `FRA`, `LAC`, `MIA`, `SG`, `HK`, `AMS`), or the raw string for a value this SDK does not know; `setPop()` takes a `Pop`, a string, or `null` to let DIDWW choose. It is no longer a relationship and cannot be included.

```php
$trunk = Didww\Item\VoiceInTrunk::find('trunk-uuid')->getData();
$trunk->setPop(Didww\Enum\Pop::AMS);
$trunk->save();
```

#### SIP Registration (2026-04-16)

A Voice In Trunk can also authenticate inbound calls via SIP registration
credentials generated by DIDWW. The SDK auto-cascades the dependent fields
the server requires:

* `setEnabledSipRegistration(true)` clears any previously-set `host` /
  `port` (the server rejects them with 422 otherwise);
* `setHost(<non-null>)` flips `enabled_sip_registration` to `false` and
  forces `use_did_in_ruri = false` so the server accepts the disable
  PATCH.

The server generates `incoming_auth_username` and `incoming_auth_password`
and surfaces them in the response when SIP registration is enabled.
Constructor arrays bypass the cascade so server responses deserialize
as-is.

```php
$sip = new Didww\Item\Configuration\Sip([
    'enabled_sip_registration' => true,
    'use_did_in_ruri' => true,
    'cnam_lookup' => true,
]);

$trunk = new Didww\Item\VoiceInTrunk(['name' => 'Office (registered)', 'configuration' => $sip]);
$created = $trunk->save()->getData();
$config = $created->getConfiguration();
// $config->getIncomingAuthUsername() — server-generated
// $config->getIncomingAuthPassword() — server-generated
```

To disable SIP registration on an existing trunk, just call `setHost()`
— the cascade flips `enabled_sip_registration` and `use_did_in_ruri` to
`false` automatically:

```php
$disable = new Didww\Item\Configuration\Sip();
$disable->setHost('sip.example.com');
$update = new Didww\Item\VoiceInTrunk(['configuration' => $disable]);
$update->setId('trunk-uuid');
$update->save();
```

### Voice In Trunk Groups

```php
$group = new Didww\Item\VoiceInTrunkGroup();
$group->setName('Primary Group');
$group->setCapacityLimit(50);
$groupDocument = $group->save();
```

### Voice Out Trunks

Voice Out Trunks use a polymorphic `authentication_method` (2026-04-16). Three types are supported:

- **`credentials_and_ip`** -- default method; `username` and `password` are server-generated and returned in the response.
- **`twilio`** -- requires a `twilio_account_sid`.
- **`ip_only`** -- read-only; can only be configured by DIDWW staff upon request. Cannot be set via the API.

```php
use Didww\Enum\OnCliMismatchAction;
use Didww\Item\AuthenticationMethod\CredentialsAndIp;

$trunk = new Didww\Item\VoiceOutTrunk();
$trunk->setName('My Outbound Trunk');
// NOTE: 203.0.113.0/24 is RFC 5737 TEST-NET-3 documentation space.
// Replace with the real CIDR of your SIP infrastructure.
$trunk->setAuthenticationMethod(new CredentialsAndIp([
    'allowed_sip_ips' => ['203.0.113.0/24'],
]));
$trunk->setOnCliMismatchAction(OnCliMismatchAction::REPLACE_CLI);
$trunk->setDefaultDid(Didww\Item\Did::build('did-uuid'));
$trunkDocument = $trunk->save();
// $trunkDocument->getData()->getAuthenticationMethod()->getUsername() -- server-generated
// $trunkDocument->getData()->getAuthenticationMethod()->getPassword() -- server-generated
```

### SMS Trunks

An SMS trunk delivers inbound messages, accepts outbound ones, or both. Its configuration is one of five classes in `Didww\Item\SmsTrunkConfiguration`:

| Type | Class | Notes |
|------|-------|-------|
| `sms_to_email` | `SmsToEmail` | Inbound messages are forwarded as email. `send_to`, `subject` and `message` are required. |
| `http_in` | `HttpIn` | Inbound messages are sent as an HTTP request to your `url`. `http_method` is a `Didww\Enum\SmsHttpMethod`; `post` and `put` also need `body_type` (a `Didww\Enum\SmsBodyType`) and `body`, which `get` refuses. Query parameters, headers and body may use the placeholders `{SMS_TEXT}`, `{SMS_TEXT_BASE64_ENCODED}`, `{SMS_SRC_ADDR}`, `{SMS_DST_ADDR}` and `{SMS_TIME}`. |
| `http_out` | `HttpOut` | You submit outbound messages over HTTP. `auth_name` and `auth_password` are generated. The submit URL is `$trunk->getHost()`. |
| `smpp_esme` | `SmppEsme` | You bind to DIDWW as an SMPP ESME. `server_location` is required; `system_id` and `password` are generated. |
| `smpp_smsc` | `SmppSmsc` | DIDWW binds to your SMSC with your `host`, `system_id`, `password` and `server_location`. |

The read-only `getAllowIn()` and `getAllowOut()` tell which directions a trunk handles. `server_location` is a `Didww\Enum\SmsServerLocation` (`NYC`, `AMS`); the sandbox accepts `nyc` only. A configuration type this SDK does not know is read as `SmsTrunkConfiguration\Generic` and sent back unchanged. Its `getAttribute()` and the `getAttributes()` of every configuration return copies: change a value with `setAttribute()` or a setter, since a value changed in place is not sent.

```php
use Didww\Enum\SmsBodyType;
use Didww\Enum\SmsHttpMethod;
use Didww\Item\SmsTrunkConfiguration\HttpIn;

$configuration = new HttpIn();
$configuration->setUrl('https://example.com/sms');
$configuration->setHttpMethod(SmsHttpMethod::POST);
$configuration->setBodyType(SmsBodyType::JSON);
$configuration->setBody('{"text":"{SMS_TEXT}","from":"{SMS_SRC_ADDR}","to":"{SMS_DST_ADDR}"}');

$trunk = new Didww\Item\SmsTrunk();
$trunk->setName('Inbound webhook');
$trunk->setConfiguration($configuration);
$trunk = $trunk->save()->getData();

// On update the configuration type must stay the same; attributes left out keep their values.
// A setter given null sends an explicit null, e.g. when switching to GET:
$update = new HttpIn();
$update->setHttpMethod(SmsHttpMethod::GET);
$update->setBodyType(null);
$update->setBody(null);
$trunk->setConfiguration($update);
$trunk = $trunk->save()->getData();

// List by configuration type, with the group
$trunks = Didww\Item\SmsTrunk::all([
    'filter' => ['configuration.type' => 'http_in,sms_to_email'],
    'include' => 'sms_trunk_group',
])->getData();
```

`setPriority()` sets the routing priority within an SMS trunk group (0-32767, the higher value first, 100 by default; it must stay `null` on `http_out` trunks). `setOutgoingDids()` replaces the whole list of DIDs a trunk may send from, and `setAllowAnyDidAsSource(true)` allows any DID of the account instead.

The generated credentials of `http_out` and `smpp_esme` trunks are read-only and are never sent back on update. `var_dump()` and `print_r()` mask them, the `password` of an `smpp_smsc` configuration, and the `headers` of an `http_in` configuration, which may carry the credentials of your endpoint. A `Generic` configuration masks every value, since its attributes are not known; only the attribute names and explicit nulls are shown. The getters return the real values. To rotate the generated credentials:

```php
$trunk = Didww\Item\SmsTrunk::build('sms-trunk-uuid');
$trunk->regenerateCredentials(); // POST /sms_trunk_regenerate_credentials

$trunk = Didww\Item\SmsTrunk::find('sms-trunk-uuid')->getData();
$configuration = $trunk->getConfiguration();
if ($configuration instanceof Didww\Item\SmsTrunkConfiguration\HttpOut) {
    // $configuration->getAuthName() and $configuration->getAuthPassword() are the new credentials
} elseif ($configuration instanceof Didww\Item\SmsTrunkConfiguration\SmppEsme) {
    // $configuration->getSystemId() and $configuration->getPassword() are the new credentials
}
```

### SMS Trunk Groups

An SMS trunk group spreads a DID's inbound messages over its trunks by their priority (the higher value first). `http_out` trunks cannot join a group. Deleting a group also deletes the trunks in it.

```php
use Swis\JsonApi\Client\Collection;

$group = new Didww\Item\SmsTrunkGroup();
$group->setName('Inbound SMS');
$group->setSmsTrunks(new Collection([Didww\Item\SmsTrunk::build('sms-trunk-uuid')]));
$group = $group->save()->getData();
echo $group->getSmsTrunksCount(); // 1
```

`setSmsTrunks()` replaces the whole membership of the group: list every trunk to keep. An empty `Collection` removes them all. A trunk can also join or leave a group with `SmsTrunk::setSmsTrunkGroup()` (`null` to leave).

### Sender ID Verifications

A Sender ID verification covers the sender IDs your outbound messages use. It is created and reviewed outside the API: the API lists and reads verifications, and changes only their name and the SMS trunk their messages are routed through. A closed verification cannot be changed. `getStatus()` returns a `Didww\Enum\SenderIdVerificationStatus` and `getSenderType()` a `Didww\Enum\SenderType`; `isActive()`, `isClosed()` and the other predicates test the status (`isStatusNew()` for status `new`). The meta carries `setup_price` and `monthly_price` as decimal strings.

```php
use Didww\Enum\SenderIdVerificationStatus;

$verifications = Didww\Item\SenderIdVerification::all([
    'filter' => ['status' => SenderIdVerificationStatus::ACTIVE->value],
    'include' => 'sms_trunk',
])->getData();
echo $verifications[0]->getMeta()['monthly_price'];

// http_out, smpp_esme or smpp_smsc trunks only. Build it with its id: save() without an id would create, which the API does not support.
$verification = Didww\Item\SenderIdVerification::build('verification-uuid');
$verification->setSmsTrunk(Didww\Item\SmsTrunk::build('sms-trunk-uuid'));
$verification->save();

// Outbound messages stop until a trunk is assigned again
$verification = Didww\Item\SenderIdVerification::build('verification-uuid');
$verification->setSmsTrunk(null);
$verification->save();
```

Deleting an SMS trunk leaves the verifications routed through it without a trunk.

### Orders

```php
use Didww\Enum\CallbackMethod;

// Order by SKU
$order = new Didww\Item\Order([
    'allow_back_ordering' => true,
    'items' => [
        new Didww\Item\OrderItem\Did(['sku_id' => 'sku-uuid', 'qty' => 2]),
    ],
]);
$order->setCallbackUrl('https://example.com/callback');
$order->setCallbackMethod(CallbackMethod::POST);
$orderDocument = $order->save();

// Order available DID
$order = new Didww\Item\Order([
    'items' => [
        new Didww\Item\OrderItem\AvailableDid([
            'sku_id' => 'sku-uuid',
            'available_did_id' => 'available-did-uuid',
        ]),
    ],
]);

// Order reserved DID
$order = new Didww\Item\Order([
    'items' => [
        new Didww\Item\OrderItem\ReservationDid([
            'sku_id' => 'sku-uuid',
            'did_reservation_id' => 'reservation-uuid',
        ]),
    ],
]);

// Order capacity
$order = new Didww\Item\Order([
    'items' => [
        new Didww\Item\OrderItem\Capacity([
            'capacity_pool_id' => 'pool-uuid',
            'qty' => 1,
        ]),
    ],
]);
```

### DID Reservations

```php
$reservation = new Didww\Item\DidReservation();
$reservation->setDescription('Reserved for client');
$reservation->setAvailableDid(Didww\Item\AvailableDid::build('available-did-uuid'));
$reservationDocument = $reservation->save();

// Delete reservation
$reservation->delete();
```

### Shared Capacity Groups

```php
$group = new Didww\Item\SharedCapacityGroup();
$group->setName('Shared Group');
$group->setSharedChannelsCount(20);
$group->setCapacityPool(Didww\Item\CapacityPool::build('pool-uuid'));
$groupDocument = $group->save();
```

### Identities

```php
use Didww\Enum\IdentityType;

$identity = new Didww\Item\Identity([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'phone_number' => '12125551234',
    'identity_type' => IdentityType::BUSINESS,
    'company_name' => 'Example Ltd',
]);
$identity->setCompanyWebsite('https://example.com'); // business identities only; null clears it
$identity->setCountry(Didww\Item\Country::build('country-uuid'));
$identityDocument = $identity->save();
```

### Addresses

```php
$address = new Didww\Item\Address();
$address->setCityName('New York');
$address->setPostalCode('10001');
$address->setAddress('123 Main St');
$address->setIdentity(Didww\Item\Identity::build('identity-uuid'));
$address->setCountry(Didww\Item\Country::build('country-uuid'));
$addressDocument = $address->save();
```

### Exports

```php
use Didww\Enum\ExportType;

// CDR export
$export = new Didww\Item\Export();
$export->setExportType(ExportType::CDR_IN);
$export->setFilterFrom('2026-10-01 00:00:00'); // inclusive, UTC
$export->setFilterTo('2026-10-02 00:00:00');   // exclusive, UTC
$export->save();

// SMS export
$export = new Didww\Item\Export();
$export->setExportType(ExportType::SMS_OUT);
$export->setFilterFrom('2026-10-01 00:00:00');
$export->setFilterTo('2026-10-02 00:00:00');
$export->setFilterServiceType('a2p');
$export->setFilterSmsTrunkId(['sms-trunk-uuid']); // sent as "sms_trunk.id"
$export = $export->save()->getData();

// The export is pending until its file is generated; read it again later
$export = Didww\Item\Export::find($export->getId())->getData();
if ($export->isCompleted()) {
    $result = $export->download('/var/data/sms_out.csv.gz'); // gzipped CSV, as served
    if (true !== $result) {
        echo "Download failed: $result\n";
    }
    $export->downloadAndDecompress('/var/data/sms_out.csv'); // plain CSV
}
```

`from` and `to` (`YYYY-MM-DD HH:MM:SS` in UTC) are required for every export type (`cdr_in`, `cdr_out`, `sms_in`, `sms_out`). The other filters depend on the type, each with its own setter:

- `cdr_in`: `setFilterDidNumber()`, `setFilterSourceNumber()`, `setFilterSourceNumberOut()`, `setFilterSourceCountryIso()`;
- `cdr_out`: `setFilterVoiceOutTrunkId()` (`voice_out_trunk.id`), `setFilterSource()`, `setFilterCli()`, `setFilterDestination()`;
- `sms_in` and `sms_out`: `setFilterSourceAddressEq()`, `setFilterSourceAddressCont()`, `setFilterDestinationAddressEq()`, `setFilterDestinationAddressCont()`, `setFilterStatus()`, `setFilterSmsId()`, `setFilterSmsTrunkId()` (`sms_trunk.id`, one id or an array of ids);
- `sms_in` only: `setFilterTrunkType()`, one or more `Didww\Enum\ExportTrunkType` values;
- `sms_out` only: `setFilterServiceType()` (`a2p` or `p2p`) and `setFilterSenderIdVerificationId()` (`sender_id_verification.id`, one id or an array of ids).

`setFilter($key, $value)` sets any other key.

`download()` and `downloadAndDecompress()` take a file path or a writable stream and return `true`, or an error message string when the download failed (an HTTP status other than 200, a transport or a file error). `download()` replaces a path only when the download succeeds. Because the bearer token is sent with it, the export URL must be on the origin (scheme, host and port) of the JSON:API base URL or of the environment's API URL, which the API builds export URLs on even when `baseUrl:` points elsewhere; any other URL throws an `\InvalidArgumentException` and nothing is sent. Redirects are not followed, and the body of an error answer is read for the message up to 1 MiB within 60 s. A token that cannot be obtained throws a `DidwwAuthException`.

### Address Verifications

```php
// List address verifications
$verifications = Didww\Item\AddressVerification::all()->getData();

// Create address verification
$verification = new Didww\Item\AddressVerification();
$verification->setCallbackUrl('https://example.com/callback');
$verification->setCallbackMethod(Didww\Enum\CallbackMethod::POST);
$verification->setAddress(Didww\Item\Address::build('address-uuid'));
$verification->setDids(new Swis\JsonApi\Client\Collection([Didww\Item\Did::build('did-uuid')]));
$verificationDocument = $verification->save();
```

### Emergency Services (2026-04-16)

```php
// List emergency requirements
$emergReqs = Didww\Item\EmergencyRequirement::all()->getData();

// Create emergency verification
$emergVerification = new Didww\Item\EmergencyVerification();
$emergVerification->setCallbackUrl('https://example.com/callback');
$emergVerification->setCallbackMethod(Didww\Enum\CallbackMethod::POST->value); // takes a string
$emergVerification->setAddress(Didww\Item\Address::build('address-uuid'));
$emergVerification->setDids(new Swis\JsonApi\Client\Collection([Didww\Item\Did::build('did-uuid')]));
$emergVerificationDocument = $emergVerification->save();

// List emergency calling services
$emergServices = Didww\Item\EmergencyCallingService::all()->getData();
```

### DID History (2026-04-16)

```php
// List DID history
$history = Didww\Item\DidHistory::all()->getData();
foreach ($history as $entry) {
    echo $entry->getAction() . ' ' . $entry->getCreatedAt()->format('Y-m-d') . "\n";
}
```

## Prices

Prices are served by three services outside the JSON:API (hosts in [Environments](#environments)). Each price list has a price file and a lookup:

| Accessor | Service | Price file | Lookup | Lookup rows |
|----------|---------|------------|--------|-------------|
| `Didww\Prices::voiceIn()` | Voice IN | `prices.csv.gz` | `lookup/prices` | `Didww\Prices\VoiceInPrice` |
| `Didww\Prices::voiceOut()` | Voice OUT | `prices.csv.gz` | `lookup/prices` | `Didww\Prices\VoiceOutPrice` |
| `Didww\Prices::smsOutA2p()` | SMS | `sms_out/a2p/prices.csv.gz` | `sms_out/a2p/lookup/prices` | `Didww\Prices\SmsOutPrice` |
| `Didww\Prices::smsOutP2p()` | SMS | `sms_out/p2p/prices.csv.gz` | `sms_out/p2p/lookup/prices` | `Didww\Prices\SmsOutPrice` |
| `Didww\Prices::smsOutOtp()` | SMS | `sms_out/otp/prices.csv.gz` | `sms_out/otp/lookup/prices` | `Didww\Prices\SmsOutPrice` |
| `Didww\Prices::smsIn()` | SMS | `sms_in/prices.csv.gz` | `sms_in/lookup/prices` | `Didww\Prices\SmsInPrice` |

Each accessor returns a `Didww\Prices\PricesService`, which uses the configuration current at the time of a call, so call `Configuration::configure()` first. The account needs a price plan of that service: without one, every call throws a `DidwwPricesException` of kind `PricesErrorKind::PRICE_PLAN_NOT_ASSIGNED`.

### Price lookups

```php
$result = Didww\Prices::voiceIn()->lookup(['number' => '18005550123', 'src_country_iso' => 'US']);
echo $result->getTotalRecords();
foreach ($result as $price) {
    if (null !== $price->getRate()) {
        printf("%s %s: %s USD/min\n", $price->getDestination(), $price->getSourceType(), $price->getRate());
    }
}

$voiceOut = Didww\Prices::voiceOut()->lookup(['number' => '493012345678']);
$voiceOut->getRows()[0]->getCategory(); // Didww\Prices\VoiceOutPriceCategory
$a2p = Didww\Prices::smsOutA2p()->lookup(['dst_number' => '4915123456789']);
$smsIn = Didww\Prices::smsIn()->lookup(['dst_country_iso' => 'US']);
```

| Service | Filters |
|---------|---------|
| Voice IN | `number` (required), `src_country_iso` |
| Voice OUT | `number` (required); no other filter |
| SMS OUT and SMS IN | `dst_number`, `dst_country_iso`; at least one |

A missing or unknown filter throws an `\InvalidArgumentException` before any request; `null` and blank values are dropped. The server checks the value formats and answers a bad value with 400 (`PricesErrorKind::BAD_REQUEST`).

`lookup()` returns a `Didww\Prices\PriceLookup`: iterate it, `count()` it, or call `getRows()` and `getTotalRecords()`. It always holds at least one row: no match throws `PricesErrorKind::PRICE_NOT_FOUND`, never an empty result. The rows are read-only objects with typed getters. Prices and fees are `?float` in USD, like the other decimal values of the SDK; they are `null` when the row rejects the destination (`getRejectCalls()` or `getRejectSms()`), and `SmsInPrice::getPrice()` is a `float`. Intervals and increments are `int` (`?int` for the scheduled `getNext*Increment()` values), `SmsOutPrice::getMcc()` and `getMnc()` are `?int`, `VoiceOutPrice::getEffectiveAt()` is a `?\DateTimeImmutable` and `VoiceOutPrice::getCategory()` is a `Didww\Prices\VoiceOutPriceCategory` (or the raw string for a value this SDK does not know).

The id of an SMS OUT or SMS IN lookup row is derived from its content: it changes when the row or its price changes, so do not use it to track a destination over time. A Voice OUT id stays the same when a re-import changes only the price. A Voice IN id names the answering row of the plan.

### Price files

The files are gzipped CSV with a header row. The SDK writes them exactly as served and never decompresses them.

```php
use Didww\Prices\PriceFileReader;

$prices = Didww\Prices::voiceOut();

// Download unless the copy at the path is current
$file = $prices->fetch('/var/data/voice_out_prices.csv.gz');
if ($file->isNotModified()) {
    echo "answered 304, the file was kept\n";
} else {
    printf("downloaded %d bytes, last modified %s\n", $file->getBytesWritten(), $file->getLastModified()?->format(DATE_ATOM));
}

// Read the rows
foreach (PriceFileReader::rows('/var/data/voice_out_prices.csv.gz') as $row) {
    echo $row['Prefix'] . ': ' . $row['Rate'] . "\n";
}

// Lower-level calls
$status = $prices->head($file->getLastModified()); // sent as If-Modified-Since; null sends none
// $status->isNotModified(), $status->getContentLength() (null when unknown),
// $status->getFilename() (from Content-Disposition, e.g. "voice_out_prices.csv.gz")

$prices->download('/var/data/voice_out_prices.csv.gz');
$stream = fopen('php://temp', 'w+b');
$prices->download($stream); // any writable stream resource or Psr\Http\Message\StreamInterface
```

- `fetch($path)` sends the modification time of the file at `$path` as `If-Modified-Since`, when the file is there; an unchanged price file is answered 304 and the file is kept. A file written by `download($path)` or `fetch()` gets the `Last-Modified` of the response as its modification time, so the date matches the copy on disk. On a stream wrapper that cannot set modification times (no `stream_metadata()`), the file keeps the time it was written, so use `download($path, $file->getLastModified())` with the date you kept instead of `fetch()` there. `fetch()` trusts the modification time of any file at `$path`: a file copied or changed by other means can be answered 304 and kept, so replace such a file with `download($path)`.
- `download($path)` and `fetch()` write the body into a temporary file next to `$path`, unique to the call, which gets the mode of a new file (`0666` minus the umask) and replaces `$path` atomically only on a 200. A 304 or an error leaves an existing file untouched, the temporary file is always removed, and concurrent downloads to one path do not mix their bytes.
- `download($stream, $ifModifiedSince = null)` writes the body into the stream only on a 200, as it arrives, so after a transfer error the stream may hold part of it. An exception thrown by the stream propagates as is.
- `head()`, `download()` and `fetch()` return a `Didww\Prices\PriceFile`: `getStatusCode()`, `getLastModified()` (`null` when absent), `getContentLength()`, `getFilename()`, `getBytesWritten()` and `isNotModified()`.
- `PriceFileReader::rows($pathOrStream)` gunzips a downloaded file and yields each row as an array keyed by the header line as is. The Voice OUT file has 15 columns. The SMS OUT files have 9: `Source prefix`, `Destination prefix`, `Country`, `MCC`, `MCC name`, `MNC`, `MNC name`, `Reject SMS` and `Price (USD, excl. VAT)`. The rows are read lazily. Input that is not gzip, a file cut short and a row whose field count differs from the header throw an `\UnexpectedValueException`, after the rows before it have been yielded. A path that cannot be opened throws a `\RuntimeException` when the rows are first read.
- Only `Last-Modified` and `If-Modified-Since` are used. The service sends no `ETag`, and the SDK never sends `If-None-Match`, which would make the service ignore `If-Modified-Since`.
- A 503 on a price file is retried up to 2 times when its `Retry-After` is 60 seconds or less, after waiting that long (at least 1 s) plus a small random delay so that clients do not retry in step. Otherwise it throws `PricesErrorKind::SERVICE_UNAVAILABLE` with `getRetryAfter()`. Lookups are not retried. The wait blocks the process: **run price file downloads from the command line or a scheduled job (cron), not within a web request.**
- A 404 `price_not_found` throws `PricesErrorKind::PRICE_NOT_FOUND`; any other 404, `price_plan_not_assigned` included, and any 404 on `head()`, which has no body, throws `PricesErrorKind::PRICE_PLAN_NOT_ASSIGNED`. `getErrorCode()` holds the code of the answer, or `price_plan_not_assigned` for a 404 without one.
- Redirects are not followed. A status without its own kind in [Error Handling](#error-handling), such as a 3xx, a 202 or a 204, throws `PricesErrorKind::UNEXPECTED_STATUS`. An error is never turned into an empty file or an empty result.

## Filtering, Sorting, and Pagination

```php
$regionsDocument = Didww\Item\Region::all([
    'filter' => ['country.id' => 'uuid', 'name' => 'Arizona'],
    'include' => 'country',
    'sort' => 'name',
    'page' => ['size' => 25, 'number' => 1],
]);
$regions = $regionsDocument->getData();
```

## Filter Reference

> See [`FILTERS.md`](FILTERS.md) for the list of `filter[KEY]` keys accepted by every list endpoint at API version `2026-10-22`.

## Error Handling

The JSON:API resources return every answer, errors included, as a document with an error collection. Check `hasErrors()` after every call.

```php
// Validation errors (422) — e.g. creating a resource with invalid data
$document = $order->save();
if ($document->hasErrors()) {
    foreach ($document->getErrors() as $error) {
        echo $error->getDetail() . "\n";
        // e.g. "name - has already been taken"
    }
}

// Not found (404)
$document = Didww\Item\Did::find('non-existent-uuid');
if ($document->hasErrors()) {
    foreach ($document->getErrors() as $error) {
        echo $error->getTitle() . ': ' . $error->getDetail() . "\n";
        // "Record not found: The record identified by non-existent-uuid could not be found."
    }
}
```

The authentication failures of the API arrive the same way: a 401 still answered after the token was replaced once, and a 403 such as `insufficient_scope` for a write with a `read` token, which is never retried, are document errors with `getStatus()` `"401"` or `"403"`. `EncryptedFile::upload()` reports any error status in its `UploadResult` (`success()`, `getResponseCode()`, `getErrors()`), and a transport failure as response code 0. `Export::download()` returns an error message for any status other than 200. A transport failure of a JSON:API call throws the Guzzle exception (`GuzzleHttp\Exception\TransferException`) as before.

The token service and the prices services throw exceptions instead, which carry a kind:

```php
use Didww\Exception\AuthErrorKind;
use Didww\Exception\DidwwAuthException;
use Didww\Exception\DidwwExceptionInterface;
use Didww\Exception\DidwwPricesException;
use Didww\Exception\PricesErrorKind;

try {
    $result = Didww\Prices::smsOutA2p()->lookup(['dst_number' => '4915123456789']);
} catch (DidwwAuthException $e) {
    if (AuthErrorKind::RATE_LIMITED === $e->getKind()) {
        // Wait getRetryAfter() seconds and try again; repeat while the kind is RATE_LIMITED.
        echo 'Token service rate limit, retry after ' . $e->getRetryAfter() . "\n";
    } elseif (AuthErrorKind::INVALID_CLIENT === $e->getKind()) {
        echo 'API key refused: ' . $e->getMessage() . "\n";
    } else {
        echo 'No access token: ' . $e->getMessage() . "\n";
    }
} catch (DidwwPricesException $e) {
    match ($e->getKind()) {
        PricesErrorKind::PRICE_NOT_FOUND => print("No price for this number\n"),
        PricesErrorKind::TOO_MANY_REQUESTS => print('Prices rate limit, retry after ' . $e->getRetryAfter() . "\n"),
        default => print('Prices error: ' . $e->getMessage() . "\n"),
    };
}

// Or catch the token service and prices exceptions together
try {
    $result = Didww\Prices::voiceIn()->lookup(['number' => '18005550123']);
} catch (DidwwExceptionInterface $e) {
    echo $e->getMessage() . "\n";
}
```

Only `DidwwAuthException` and `DidwwPricesException` implement `Didww\Exception\DidwwExceptionInterface`. Their messages never contain the API key or a token. The interface does not cover the other exceptions the SDK can throw: the Guzzle exception (`GuzzleHttp\Exception\TransferException`) for a transport failure of a JSON:API call, `\InvalidArgumentException` for invalid arguments, filters or configuration, and the `\RuntimeException` and `\UnexpectedValueException` of the prices services and `PriceFileReader` described below and in [Price files](#price-files).

**Token service** (`DidwwAuthException`, with `getKind()`, `getStatusCode()`, `getError()`, `getErrorDescription()` and `getRetryAfter()`). It is thrown from any call that needs a new token, JSON:API calls included:

| `AuthErrorKind` | Thrown when |
|-----------------|-------------|
| `INVALID_CLIENT` | 401: the token service refused the API key. Not retried. |
| `INVALID_REQUEST` | 400, e.g. `invalid_request` or `invalid_scope`. |
| `RATE_LIMITED` | 429. `getRetryAfter()` is the server's wait. Further token requests fail without a request until that time plus a small random delay has passed; such a refusal reports the time still left in `getRetryAfter()`, so retry in a loop rather than once. |
| `SERVER_ERROR` | 5xx on the last of the 4 attempts. |
| `CONNECTION_ERROR` | Connection failure or timeout on the last attempt, a TLS failure, or the token request could not be sent, for example because of an invalid HTTP client option (the last two are not retried). |
| `INVALID_GRANT` | The token is empty or does not cover the requested audience. |
| `UNEXPECTED_STATUS` | Any other status, such as a redirect. |

**Prices** (`DidwwPricesException`, with `getKind()`, `getStatusCode()`, `getErrorCode()`, `getErrors()` and `getRetryAfter()`):

| `PricesErrorKind` | Status |
|-------------------|--------|
| `BAD_REQUEST` | 400, e.g. a filter value of the wrong format |
| `NOT_AUTHORIZED` | 401 after the token was replaced once |
| `ACCESS_DENIED` | 403 |
| `PRICE_PLAN_NOT_ASSIGNED` | 404 `price_plan_not_assigned`, and any other 404 except `price_not_found`, including any 404 on `head()`, which has no body |
| `PRICE_NOT_FOUND` | 404 `price_not_found`: no price matches the lookup |
| `NOT_ACCEPTABLE` | 406 |
| `TOO_MANY_REQUESTS` | 429, with `getRetryAfter()`. Not retried. |
| `SERVICE_UNAVAILABLE` | 503, with `getRetryAfter()`. Retried on price files only, see [Price files](#price-files). |
| `UNEXPECTED_STATUS` | Any other status |
| `CONNECTION_ERROR` | Connection failure, timeout, TLS failure or a response body that breaks off. Not retried. An exception writing the destination stream propagates as is. |

The prices services can also throw a `DidwwAuthException`, `lookup()` an `\InvalidArgumentException` for invalid filters and an `\UnexpectedValueException` when a 200 answer is not a JSON:API document it can parse or holds no prices, and `download()` and `fetch()` a `\RuntimeException` for the file errors of the path (temporary file, times, rename).

## All Supported Resources

| Resource | Class | Operations |
|----------|-------|------------|
| Country | `Didww\Item\Country` | list, find |
| Region | `Didww\Item\Region` | list, find |
| City | `Didww\Item\City` | list, find |
| Area | `Didww\Item\Area` | list, find |
| NanpaPrefix | `Didww\Item\NanpaPrefix` | list, find |
| DidGroup | `Didww\Item\DidGroup` | list, find |
| AvailableDid | `Didww\Item\AvailableDid` | list, find |
| ProofType | `Didww\Item\ProofType` | list, find |
| PublicKey | `Didww\Item\PublicKey` | list, find |
| AddressRequirement | `Didww\Item\AddressRequirement` | list, find |
| SupportingDocumentTemplate | `Didww\Item\SupportingDocumentTemplate` | list, find |
| Balance | `Didww\Item\Balance` | find |
| Did | `Didww\Item\Did` | list, find, update |
| VoiceInTrunk | `Didww\Item\VoiceInTrunk` | list, find, create, update, delete |
| VoiceInTrunkGroup | `Didww\Item\VoiceInTrunkGroup` | list, find, create, update, delete |
| VoiceOutTrunk | `Didww\Item\VoiceOutTrunk` | list, find, create, update, delete |
| VoiceOutTrunkRegenerateCredential | `Didww\Item\VoiceOutTrunkRegenerateCredential` | create |
| SmsTrunk | `Didww\Item\SmsTrunk` | list, find, create, update, delete |
| SmsTrunkGroup | `Didww\Item\SmsTrunkGroup` | list, find, create, update, delete |
| SmsTrunkRegenerateCredential | `Didww\Item\SmsTrunkRegenerateCredential` | create |
| SenderIdVerification | `Didww\Item\SenderIdVerification` | list, find, update |
| DidReservation | `Didww\Item\DidReservation` | list, find, create, delete |
| CapacityPool | `Didww\Item\CapacityPool` | list, find, update |
| SharedCapacityGroup | `Didww\Item\SharedCapacityGroup` | list, find, create, update, delete |
| Order | `Didww\Item\Order` | list, find, create, delete |
| Export | `Didww\Item\Export` | list, find, create, update |
| Address | `Didww\Item\Address` | list, find, create, update, delete |
| AddressVerification | `Didww\Item\AddressVerification` | list, find, create, update |
| Identity | `Didww\Item\Identity` | list, find, create, update, delete |
| EncryptedFile | `Didww\Item\EncryptedFile` | list, find, delete, upload |
| PermanentSupportingDocument | `Didww\Item\PermanentSupportingDocument` | create, delete |
| Proof | `Didww\Item\Proof` | create, delete |
| AddressRequirementValidation | `Didww\Item\AddressRequirementValidation` | create |
| DidHistory | `Didww\Item\DidHistory` | list |
| EmergencyRequirement | `Didww\Item\EmergencyRequirement` | list, find |
| EmergencyRequirementValidation | `Didww\Item\EmergencyRequirementValidation` | create |
| EmergencyCallingService | `Didww\Item\EmergencyCallingService` | list, find, delete |
| EmergencyVerification | `Didww\Item\EmergencyVerification` | list, find, create, update |
| VoiceInPrice | `Didww\Prices::voiceIn()` | lookup, head, download, fetch |
| VoiceOutPrice | `Didww\Prices::voiceOut()` | lookup, head, download, fetch |
| SmsOutPrice | `Didww\Prices::smsOutA2p()`, `smsOutP2p()`, `smsOutOtp()` | lookup, head, download, fetch |
| SmsInPrice | `Didww\Prices::smsIn()` | lookup, head, download, fetch |

The price rows are served by the prices services outside the JSON:API, see [Prices](#prices).

## Date and Datetime Fields

Date and datetime attributes returned from the API are represented as strings in the underlying JSON. This SDK provides **typed getter methods** for some known date and datetime attributes; these getters convert those string values to `\DateTime` instances.

- **Datetime getters** — return `\DateTime` with full timestamp:
  - `getCreatedAt()` — present on most resources
  - `getUpdatedAt()` — `SmsTrunk`, `SmsTrunkGroup`
  - `getExpiresAt()` — `Did`, `DidReservation`, `Proof`, `EncryptedFile` (nullable)
  - `getActivatedAt()` — `EmergencyCallingService`, `SenderIdVerification` (nullable)
  - `getCanceledAt()` — `EmergencyCallingService` (nullable)
  - `getClosedAt()` — `SenderIdVerification` (nullable)
- **Date-only getters** — return `\DateTime` with time `00:00:00` in the default time zone of PHP:
  - `Identity::getBirthDate()`
  - `EmergencyCallingService::getRenewDate()` (nullable)
- **Getters that return date-only strings** — `"YYYY-MM-DD"`:
  - `CapacityPool::getRenewDate()` (nullable)
  - `SenderIdVerification::getRenewDate()` — nullable, `null` once the verification is closed
  - Order item `getBilledFrom()` / `getBilledTo()`
- **Prices** — `\DateTimeImmutable`:
  - `VoiceOutPrice::getEffectiveAt()` (nullable)
  - `PriceFile::getLastModified()` (nullable, UTC)
- **String fields** (not numeric):
  - `EmergencyRequirement::getEstimateSetupTime()` — e.g. `"7-14 days"`, `"1"`
  - `EmergencyRequirement::getRequirementRestrictionMessage()` — nullable

**Important changes from previous API versions:**
- `getExpireAt()` renamed to `getExpiresAt()` on `DidReservation` and `EncryptedFile`
- `CapacityPool::getRenewDate()` returns a date-only string, NOT a `\DateTime`
- `getEstimateSetupTime()` returns a string, NOT an integer

```php
$did = Didww\Item\Did::find('uuid')->getData();
echo $did->getCreatedAt()->format('Y-m-d H:i:s');  // e.g. "2024-01-15 10:00:00"
echo $did->getExpiresAt()?->format('Y-m-d');        // null or "2025-01-15"

$identity = Didww\Item\Identity::find('uuid')->getData();
echo $identity->getBirthDate()->format('Y-m-d');    // e.g. "1990-05-20"
```

## Enums

The SDK provides PHP 8.2+ backed enum classes for all API option fields (for example `CallbackMethod`, `IdentityType`, `OrderStatus`, `ExportType`, `CliFormat`, `OnCliMismatchAction`, `MediaEncryptionMode`, `TransportProtocol`, `Codec`, and more).

```php
use Didww\Enum\CallbackMethod;
use Didww\Enum\IdentityType;

$order = new Didww\Item\Order();
$order->setCallbackMethod(CallbackMethod::POST);

$identity = new Didww\Item\Identity();
$identity->setIdentityType(IdentityType::BUSINESS);
```

All setters accept both enum constants and raw string/integer values for backward compatibility:

```php
use Didww\Enum\OnCliMismatchAction;

// Both are equivalent
$trunk->setOnCliMismatchAction(OnCliMismatchAction::REPLACE_CLI);
$trunk->setOnCliMismatchAction('replace_cli');
```

Getters of the newer attributes (`getNumberType()`, `getPop()`, the SMS trunk configuration and Sender ID verification getters, `VoiceOutPrice::getCategory()`) return the enum, or the raw string for a value this SDK version does not know yet.

### Available Enums

Namespace `Didww\Enum`, unless noted:

| Enum | Backing Type | Values |
|------|-------------|--------|
| `CliFormat` | `string` | `RAW`, `E164`, `LOCAL` |
| `IdentityType` | `string` | `PERSONAL`, `BUSINESS` |
| `OrderStatus` | `string` | `PENDING`, `CANCELED`, `COMPLETED` |
| `CallbackMethod` | `string` | `POST`, `GET` |
| `ExportType` | `string` | `CDR_IN`, `CDR_OUT`, `SMS_IN`, `SMS_OUT` |
| `ExportStatus` | `string` | `PENDING`, `PROCESSING`, `COMPLETED` |
| `ExportTrunkType` | `string` | `SMPP_SMSC`, `SMPP_ESME`, `EMAIL`, `HTTP_IN` (`trunk_type` filter of `sms_in` exports) |
| `AddressVerificationStatus` | `string` | `PENDING`, `APPROVED`, `REJECTED` |
| `MediaEncryptionMode` | `string` | `DISABLED`, `SRTP_SDES`, `SRTP_DTLS`, `ZRTP` |
| `StirShakenMode` | `string` | `DISABLED`, `ORIGINAL`, `PAI`, `ORIGINAL_PAI`, `VERSTAT` |
| `OnCliMismatchAction` | `string` | `SEND_ORIGINAL_CLI`, `REJECT_CALL`, `REPLACE_CLI`\*, `RANDOMIZE_CLI`\* |
| `DefaultDstAction` | `string` | `ALLOW_ALL`, `REJECT_ALL` |
| `VoiceOutTrunkStatus` | `string` | `ACTIVE`, `BLOCKED` |
| `Feature` | `string` | `VOICE_IN`, `VOICE_OUT`, `T38`, `SMS_IN`, `P2P`, `A2P`, `EMERGENCY`, `CNAM_OUT` |
| `AreaLevel` | `string` | `WORLDWIDE`, `COUNTRY`, `AREA`, `CITY` |
| `NumberType` | `string` | `LOCAL`, `NATIONAL`, `MOBILE`, `TOLL_FREE`, `SHARED_COST`, `GLOBAL` |
| `Pop` | `string` | `NYC`, `FRA`, `LAC`, `MIA`, `SG`, `HK`, `AMS` |
| `SenderIdVerificationStatus` | `string` | `ACTIVE`, `CHANGES_REQUIRED`, `CLOSED`, `CLOSING_IN_PROCESS`, `IN_PROCESS`, `NEW` |
| `SenderType` | `string` | `ALPHANUMERIC`, `CARRIER`, `LONG_CODE`, `SHORT_CODE`, `TOLL_FREE` |
| `SmsHttpMethod` | `string` | `GET`, `POST`, `PUT` |
| `SmsBodyType` | `string` | `JSON`, `HTML_MULTIPART`, `HTML_URL_ENCODED`, `RAW` |
| `SmsServerLocation` | `string` | `NYC`, `AMS` |
| `Codec` | `int` | `TELEPHONE_EVENT(6)`, `G723(7)`, `G729(8)`, `PCMU(9)`, `PCMA(10)`, ... |
| `TransportProtocol` | `int` | `UDP(1)`, `TCP(2)`, `TLS(3)` |
| `RxDtmfFormat` | `int` | `RFC_2833(1)`, `SIP_INFO(2)`, `RFC_2833_OR_SIP_INFO(3)` |
| `TxDtmfFormat` | `int` | `DISABLED(0)`, `RFC_2833(1)`, `SIP_INFO_RELAY(2)`, `SIP_INFO_DTMF(4)` |
| `SstRefreshMethod` | `int` | `INVITE(1)`, `UPDATE(2)`, `UPDATE_FALLBACK_INVITE(3)` |
| `EmergencyCallingServiceStatus` | `string` | `ACTIVE`, `CANCELED`, `CHANGES_REQUIRED`, `IN_PROCESS`, `NEW`, `PENDING_UPDATE` |
| `EmergencyVerificationStatus` | `string` | `PENDING`, `APPROVED`, `REJECTED` |
| `DiversionRelayPolicy` | `string` | `NONE`, `AS_IS`, `SIP`, `TEL` |
| `ReroutingDisconnectCode` | `int` | 47 SIP error codes (56-108, 1505) |
| `Didww\Prices\VoiceOutPriceCategory` | `string` | `INTERNATIONAL`, `ORIGIN_BASED`, `LOCAL` |
| `Didww\Auth\Scope` | `string` | `READ`, `FULL` |
| `Didww\Exception\AuthErrorKind`, `Didww\Exception\PricesErrorKind` | `string` | See [Error Handling](#error-handling) |

\* `REPLACE_CLI` and `RANDOMIZE_CLI` require additional account configuration. Contact DIDWW support to enable these values.

## File Encryption

The SDK provides an `Encrypt` utility for encrypting files before upload, using RSA-OAEP + AES-256-CBC (matching DIDWW's encryption requirements).

```php
$fileContents = file_get_contents('/path/to/document.pdf');
$enc = new Didww\Encrypt();
$fingerprint = $enc->getFingerprint();
$encData = $enc->encrypt($fileContents);
$uploadResult = Didww\Item\EncryptedFile::upload($fingerprint, $encData, 'file description');
if ($uploadResult->success()) {
    echo $uploadResult->getId() . "\n";
}
```

## Webhook Signature Validation

Validate incoming webhook callbacks from DIDWW using HMAC-SHA1 signature verification.

Initialize the validator with the callback secret that is enabled in the DIDWW User Panel (**APIs → DIDWW API 3 → Callback Secrets**). DIDWW signs every callback with it and sends callbacks only while a callback secret is enabled.

```php
$validator = new Didww\Callback\RequestValidator('YOUR_CALLBACK_SECRET');

$valid = $validator->validate(
    $requestUrl,      // full original URL
    $payloadParams,   // array of payload key-value pairs
    $signature         // value of X-DIDWW-Signature header
);
```

## Trunk Configuration Types

| Type | Class |
|------|-------|
| SIP | `Didww\Item\Configuration\Sip` |
| PSTN | `Didww\Item\Configuration\Pstn` |

For the SMS trunk configuration types, see [SMS Trunks](#sms-trunks).

## Order Item Types

| Type | Class |
|------|-------|
| DID | `Didww\Item\OrderItem\Did` |
| Available DID | `Didww\Item\OrderItem\AvailableDid` |
| Reservation DID | `Didww\Item\OrderItem\ReservationDid` |
| Capacity | `Didww\Item\OrderItem\Capacity` |
| Emergency | `Didww\Item\OrderItem\Emergency` |
| Generic | `Didww\Item\OrderItem\Generic` |

## Migrating from 10.x

11.0.0 targets API `2026-10-22` only. To stay on API `2026-04-16`, keep 10.x (`^10.2`, branch [`release-10`](https://github.com/didww/didww-api-3-php-sdk/tree/release-10)).

Most code keeps working: `new Credentials($apiKey, $env)` and `Configuration::configure($credentials, $httpClientConfig)` are unchanged, and the SDK mints the access tokens itself. The breaking changes:

- **Authentication.** Every request carries a bearer token minted from the API key (see [Authentication](#authentication)). The `Api-Key` header is sent only to the token service, so outgoing traffic must reach `auth.didww.com` (`auth-sandbox.didww.com` in the sandbox). The token service can throw a `DidwwAuthException` from any call.
- **API version.** `X-DIDWW-API-Version` is no longer sent: the version is requested in the token. The default is `2026-10-22`, and an earlier version throws an `\InvalidArgumentException`. A `null` version in `Credentials` now means the default, not "send no version".
- **Validation.** `Credentials` and `configure()` throw an `\InvalidArgumentException` for invalid values (see [Validation](#validation)). An environment other than `production` and `sandbox` is rejected; 10.x fell back to the sandbox.
- **Removed methods.** `Didww\Client::setApiKey()` and `Client::setVersion()` are removed: authentication and the API version come from `Credentials` and `configure()`.
- **Removed resources.** `Didww\Item\Pop` and `Didww\Item\DidGroupType` are removed, with the `did_group_type` relationship. `DidGroup`, `AddressRequirement`, `EmergencyRequirement` and `EmergencyCallingService` have `getNumberType()` instead of `didGroupType()`; filter with `number_type` instead of `did_group_type.id`. `VoiceInTrunk::pop()` is removed and `setPop()` takes a `Didww\Enum\Pop`, a string or `null`; read it with `getPop()`.
- **HTTP defaults.** 10.x used the Guzzle defaults (redirects followed, responses decompressed, no timeouts). 11.x connects within 10 s, aborts a transfer that stalls (under 1 byte/s) for 60 s, follows no redirects and decompresses nothing (see [HTTP client](#http-client-proxy-tls-timeouts)). A `timeout` you pass, such as `['timeout' => 20]`, still works, but it now also bounds price and export file downloads.
- **Export download and file upload.** `Export::download()`, `Export::downloadAndDecompress()` and `EncryptedFile::upload()` go through the Guzzle client of the SDK with the bearer token. An export downloads only from the API origin (an `\InvalidArgumentException` otherwise), only a 200 succeeds, and redirects are not followed; the `true|string` result is unchanged.
- **Guzzle 8.** `guzzlehttp/guzzle` `^8.0.1` is required (10.x allowed Guzzle 7). Guzzle 8 changes its own API, so update any code of yours that uses Guzzle directly, such as a `handler`, middleware or callbacks you pass in `$httpClientConfig`, following Guzzle's upgrade guide. In particular a transport failure of a JSON:API call now throws Guzzle 8's exception classes: only a `GuzzleHttp\Exception\ResponseException` (such as a timeout after the response headers, `ResponseTimeoutException`) has `getResponse()`, a `ConnectException` or `NetworkException` has none, and `getHandlerContext()` is gone.
- **Requirements.** `ext-zlib` and `psr/log` are now required.

New in 11.0.0: [SMS trunks](#sms-trunks), [SMS trunk groups](#sms-trunk-groups), [Sender ID verifications](#sender-id-verifications), [DID SMS routing](#sms-routing), `sms_in` and `sms_out` [exports](#exports), [prices](#prices), `DidGroup::getIsDemo()`, `Identity` company website, and the `scope:`, `tokenStore:`, `logger:` and URL arguments of `configure()`.

## Development

```sh
composer install
vendor/bin/phpunit
composer check-style
```

## Contributing

Bug reports and pull requests are welcome on GitHub at https://github.com/didww/didww-api-3-php-sdk

## License

The package is available as open source under the terms of the [MIT License](https://opensource.org/licenses/MIT).
