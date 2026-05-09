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

This branch targets **DIDWW API version `2026-04-16`**. If you need support for the previous API version `2022-05-10`, use the [`2022-05-10`](../../tree/2022-05-10) branch.

By default, this SDK sends the `X-DIDWW-API-Version: 2026-04-16` header with each request.

## Requirements

- PHP 8.2+

## Installation

```sh
composer require didww/didww-api-3-php-sdk
```

## Usage

```php
$credentials = new Didww\Credentials('YOUR_API_KEY', 'sandbox');
Didww\Configuration::configure($credentials, ['timeout' => 20]);

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
// Sandbox
$credentials = new Didww\Credentials('YOUR_API_KEY', 'sandbox');
Didww\Configuration::configure($credentials, ['timeout' => 20]);

// Production
$credentials = new Didww\Credentials('YOUR_API_KEY', 'production');
Didww\Configuration::configure($credentials, ['timeout' => 20]);
```

### Environments

| Environment | Base URL |
|-------------|----------|
| `production` | `https://api.didww.com/v3` |
| `sandbox` | `https://sandbox-api.didww.com/v3` |

## Resources

### Read-Only Resources

```php
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

// POPs (Points of Presence)
$pops = Didww\Item\Pop::all()->getData();

// DID Group Types
$types = Didww\Item\DidGroupType::all()->getData();

// DID Groups (with stock keeping units)
$groups = Didww\Item\DidGroup::all(['include' => 'stock_keeping_units'])->getData();

// Available DIDs
$available = Didww\Item\AvailableDid::all([
    'include' => 'did_group.stock_keeping_units',
])->getData();

// Proof Types
$proofTypes = Didww\Item\ProofType::all()->getData();

// Public Keys
$publicKeys = Didww\Item\PublicKey::all()->getData();

// Address Requirements (renamed from Requirements in 2026-04-16)
$requirements = Didww\Item\AddressRequirement::all()->getData();

// Balance (singleton)
$balance = Didww\Item\Balance::find()->getData();
```

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
    'identity_type' => IdentityType::PERSONAL,
]);
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

In 2026-04-16, the `year`/`month` filters are replaced by a `from`/`to` datetime range.

```php
use Didww\Enum\ExportType;

$export = new Didww\Item\Export();
$export->setExportType(ExportType::CDR_IN);
$export->setFilterFrom('2025-01-01T00:00:00.000Z');  // inclusive
$export->setFilterTo('2025-02-01T00:00:00.000Z');     // exclusive
$exportDocument = $export->save();

// Download the export when completed
$export = $exportDocument->getData();
$export->download('/tmp/export.csv');
```

### Address Verifications

```php
// List address verifications
$verifications = Didww\Item\AddressVerification::all()->getData();

// Create address verification
$verification = new Didww\Item\AddressVerification();
$verification->setCallbackUrl('https://example.com/callback');
$verification->setCallbackMethod(Didww\Enum\CallbackMethod::POST);
$verification->setAddress(Didww\Item\Address::build('address-uuid'));
$verification->setDids([Didww\Item\Did::build('did-uuid')]);
$verificationDocument = $verification->save();
```

### Emergency Services (2026-04-16)

```php
// List emergency requirements
$emergReqs = Didww\Item\EmergencyRequirement::all()->getData();

// Create emergency verification
$emergVerification = new Didww\Item\EmergencyVerification();
$emergVerification->setCallbackUrl('https://example.com/callback');
$emergVerification->setCallbackMethod(Didww\Enum\CallbackMethod::POST);
$emergVerification->setAddress(Didww\Item\Address::build('address-uuid'));
$emergVerification->setDids([Didww\Item\Did::build('did-uuid')]);
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

## Date and Datetime Fields

Date and datetime attributes returned from the API are represented as strings in the underlying JSON. This SDK provides **typed getter methods** for some known date and datetime attributes; these getters convert those string values to `\DateTime` instances.

- **Datetime getters** — return `\DateTime` with full timestamp:
  - `getCreatedAt()` — present on most resources
  - `getExpiresAt()` — `Did`, `DidReservation`, `Proof`, `EncryptedFile` (nullable)
  - `getActivatedAt()` — `EmergencyCallingService` (nullable)
  - `getCanceledAt()` — `EmergencyCallingService` (nullable)
- **Date-only getters** — return `\DateTime` with time `00:00:00`:
  - `Identity::getBirthDate()`
- **Getters that intentionally keep strings** — these represent dates but still return `string` values:
  - `CapacityPool::getRenewDate()`, `EmergencyCallingService::getRenewDate()` — `"YYYY-MM-DD"` (nullable)
  - Order item `getBilledFrom()` / `getBilledTo()`
- **String fields** (not numeric):
  - `EmergencyRequirement::getEstimateSetupTime()` — e.g. `"7-14 days"`, `"1"`
  - `EmergencyRequirement::getRequirementRestrictionMessage()` — nullable

**Important changes from previous API versions:**
- `getExpireAt()` renamed to `getExpiresAt()` on `DidReservation` and `EncryptedFile`
- `getRenewDate()` returns a date-only string, NOT a `\DateTime`
- `getEstimateSetupTime()` returns a string, NOT an integer

```php
$did = Didww\Item\Did::find('uuid')->getData();
echo $did->getCreatedAt()->format('Y-m-d H:i:s');  // e.g. "2024-01-15 10:00:00"
echo $did->getExpiresAt()?->format('Y-m-d');        // null or "2025-01-15"

$identity = Didww\Item\Identity::find('uuid')->getData();
echo $identity->getBirthDate()->format('Y-m-d');    // e.g. "1990-05-20"
```

## Filtering, Sorting, and Pagination

> See [`FILTERS.md`](FILTERS.md) for the canonical list of `filter[KEY]` keys accepted by every list endpoint, verified live against the DIDWW API at version `2026-04-16`.

```php
$regionsDocument = Didww\Item\Region::all([
    'filter' => ['country.id' => 'uuid', 'name' => 'Arizona'],
    'include' => 'country',
    'sort' => 'name',
    'page' => ['size' => 25, 'number' => 1],
]);
$regions = $regionsDocument->getData();
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

### Available Enums

| Enum | Backing Type | Values |
|------|-------------|--------|
| `CliFormat` | `string` | `RAW`, `E164`, `LOCAL` |
| `IdentityType` | `string` | `PERSONAL`, `BUSINESS` |
| `OrderStatus` | `string` | `PENDING`, `CANCELED`, `COMPLETED` |
| `CallbackMethod` | `string` | `POST`, `GET` |
| `ExportType` | `string` | `CDR_IN`, `CDR_OUT` |
| `ExportStatus` | `string` | `PENDING`, `PROCESSING`, `COMPLETED` |
| `AddressVerificationStatus` | `string` | `PENDING`, `APPROVED`, `REJECTED` |
| `MediaEncryptionMode` | `string` | `DISABLED`, `SRTP_SDES`, `SRTP_DTLS`, `ZRTP` |
| `StirShakenMode` | `string` | `DISABLED`, `ORIGINAL`, `PAI`, `ORIGINAL_PAI`, `VERSTAT` |
| `OnCliMismatchAction` | `string` | `SEND_ORIGINAL_CLI`, `REJECT_CALL`, `REPLACE_CLI`\*, `RANDOMIZE_CLI`\* |
| `DefaultDstAction` | `string` | `ALLOW_ALL`, `REJECT_ALL` |
| `VoiceOutTrunkStatus` | `string` | `ACTIVE`, `BLOCKED` |
| `Feature` | `string` | `VOICE_IN`, `VOICE_OUT`, `T38`, `SMS_IN`, `P2P`, `A2P`, `EMERGENCY`, `CNAM_OUT` |
| `AreaLevel` | `string` | `WORLDWIDE`, `COUNTRY`, `AREA`, `CITY` |
| `Codec` | `int` | `TELEPHONE_EVENT(6)`, `G723(7)`, `G729(8)`, `PCMU(9)`, `PCMA(10)`, ... |
| `TransportProtocol` | `int` | `UDP(1)`, `TCP(2)`, `TLS(3)` |
| `RxDtmfFormat` | `int` | `RFC_2833(1)`, `SIP_INFO(2)`, `RFC_2833_OR_SIP_INFO(3)` |
| `TxDtmfFormat` | `int` | `DISABLED(0)`, `RFC_2833(1)`, `SIP_INFO_RELAY(2)`, `SIP_INFO_DTMF(4)` |
| `SstRefreshMethod` | `int` | `INVITE(1)`, `UPDATE(2)`, `UPDATE_FALLBACK_INVITE(3)` |
| `EmergencyCallingServiceStatus` | `string` | `ACTIVE`, `CANCELED`, `CHANGES_REQUIRED`, `IN_PROCESS`, `NEW`, `PENDING_UPDATE` |
| `EmergencyVerificationStatus` | `string` | `PENDING`, `APPROVED`, `REJECTED` |
| `DiversionRelayPolicy` | `string` | `NONE`, `AS_IS`, `SIP`, `TEL` |
| `ReroutingDisconnectCode` | `int` | 47 SIP error codes (56-108, 1505) |

\* `REPLACE_CLI` and `RANDOMIZE_CLI` require additional account configuration. Contact DIDWW support to enable these values.

## File Encryption

The SDK provides an `Encrypt` utility for encrypting files before upload, using RSA-OAEP + AES-256-CBC (matching DIDWW's encryption requirements).

```php
$fileContents = file_get_contents('/path/to/document.pdf');
$enc = new Didww\Encrypt();
$fingerprint = $enc->getFingerprint();
$encData = $enc->encrypt($fileContents);
$uploadResult = Didww\Item\EncryptedFile::upload($fingerprint, [$encData], ['file description']);
```

## Webhook Signature Validation

Validate incoming webhook callbacks from DIDWW using HMAC-SHA1 signature verification.

```php
$validator = new Didww\Callback\RequestValidator('YOUR_API_KEY');

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

## Order Item Types

| Type | Class |
|------|-------|
| DID | `Didww\Item\OrderItem\Did` |
| Available DID | `Didww\Item\OrderItem\AvailableDid` |
| Reservation DID | `Didww\Item\OrderItem\ReservationDid` |
| Capacity | `Didww\Item\OrderItem\Capacity` |
| Generic | `Didww\Item\OrderItem\Generic` |

## Error Handling

All API responses (including errors) are returned as document objects with an error collection. Check `hasErrors()` after every API call.

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

// Unauthorized (401) — e.g. invalid API key
$document = Didww\Item\Balance::find();
if ($document->hasErrors()) {
    foreach ($document->getErrors() as $error) {
        echo $error->getTitle() . ': ' . $error->getDetail() . "\n";
        // "Unauthorized: Authorization failed"
    }
}
```

## All Supported Resources

| Resource | Class | Operations |
|----------|-------|------------|
| Country | `Didww\Item\Country` | list, find |
| Region | `Didww\Item\Region` | list, find |
| City | `Didww\Item\City` | list, find |
| Area | `Didww\Item\Area` | list, find |
| NanpaPrefix | `Didww\Item\NanpaPrefix` | list, find |
| Pop | `Didww\Item\Pop` | list, find |
| DidGroupType | `Didww\Item\DidGroupType` | list, find |
| DidGroup | `Didww\Item\DidGroup` | list, find |
| AvailableDid | `Didww\Item\AvailableDid` | list, find |
| ProofType | `Didww\Item\ProofType` | list, find |
| PublicKey | `Didww\Item\PublicKey` | list, find |
| AddressRequirement | `Didww\Item\AddressRequirement` | list, find |
| SupportingDocumentTemplate | `Didww\Item\SupportingDocumentTemplate` | list, find |
| Balance | `Didww\Item\Balance` | find |
| Did | `Didww\Item\Did` | list, find, update, delete |
| VoiceInTrunk | `Didww\Item\VoiceInTrunk` | list, find, create, update, delete |
| VoiceInTrunkGroup | `Didww\Item\VoiceInTrunkGroup` | list, find, create, update, delete |
| VoiceOutTrunk | `Didww\Item\VoiceOutTrunk` | list, find, create, update, delete |
| VoiceOutTrunkRegenerateCredential | `Didww\Item\VoiceOutTrunkRegenerateCredential` | create |
| DidReservation | `Didww\Item\DidReservation` | list, find, create, delete |
| CapacityPool | `Didww\Item\CapacityPool` | list, find |
| SharedCapacityGroup | `Didww\Item\SharedCapacityGroup` | list, find, create, update, delete |
| Order | `Didww\Item\Order` | list, find, create |
| Export | `Didww\Item\Export` | list, find, create, update |
| Address | `Didww\Item\Address` | list, find, create, delete |
| AddressVerification | `Didww\Item\AddressVerification` | list, create, update |
| Identity | `Didww\Item\Identity` | list, find, create, delete |
| EncryptedFile | `Didww\Item\EncryptedFile` | list, find, delete |
| PermanentSupportingDocument | `Didww\Item\PermanentSupportingDocument` | create, delete |
| Proof | `Didww\Item\Proof` | create, delete |
| AddressRequirementValidation | `Didww\Item\AddressRequirementValidation` | create |
| DidHistory | `Didww\Item\DidHistory` | list |
| EmergencyRequirement | `Didww\Item\EmergencyRequirement` | list, find |
| EmergencyRequirementValidation | `Didww\Item\EmergencyRequirementValidation` | create |
| EmergencyCallingService | `Didww\Item\EmergencyCallingService` | list, find, update |
| EmergencyVerification | `Didww\Item\EmergencyVerification` | list, find, create, update |

## Contributing

Bug reports and pull requests are welcome on GitHub at https://github.com/didww/didww-api-3-php-sdk

## License

The package is available as open source under the terms of the [MIT License](https://opensource.org/licenses/MIT).
