PHP client for DIDWW API v3.

[![PHP from Packagist](https://img.shields.io/packagist/php-v/didww/didww-api-3-php-sdk.svg)](https://packagist.org/packages/didww/didww-api-3-php-sdk)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/didww/didww-api-3-php-sdk.svg)](https://packagist.org/packages/didww/didww-api-3-php-sdk)
![Tests](https://github.com/didww/didww-api-3-php-sdk/workflows/Tests/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/didww/didww-api-3-php-sdk/badge.svg?branch=master)](https://coveralls.io/github/didww/didww-api-3-php-sdk?branch=master)

## About DIDWW API v3

The DIDWW API provides a simple yet powerful interface that allows you to fully integrate your own applications with DIDWW services. An extensive set of actions may be performed using this API, such as ordering and configuring phone numbers, setting capacity, creating SIP trunks and retrieving CDRs and other operational data.

The DIDWW API v3 is a fully compliant implementation of the [JSON API specification](http://jsonapi.org/format/).

Read more https://doc.didww.com/api

## Requirements

- PHP 8.2+

## Installation

```sh
composer require didww/didww-api-3-php-sdk
```

## Usage

```php
$credentials = new \Didww\Credentials('YOUR_API_KEY', 'sandbox');
\Didww\Configuration::configure($credentials, ['timeout' => 20]);

// Check balance
$balance = \Didww\Item\Balance::find()->getData();
echo "Balance: " . $balance->getTotalBalance();

// List DID groups with stock keeping units
$didGroups = \Didww\Item\DidGroup::all([
    'include' => 'stock_keeping_units',
    'filter' => ['area_name' => 'Acapulco'],
])->getData();
```

For more examples visit [examples/](examples/).

For details on obtaining your API key please visit https://doc.didww.com/api#introduction-api-keys

## Examples

- Source code: [examples/](examples/)
- How to run: [examples/README.md](examples/README.md)

## Configuration

```php
// Sandbox
$credentials = new \Didww\Credentials('YOUR_API_KEY', 'sandbox');
\Didww\Configuration::configure($credentials, ['timeout' => 20]);

// Production
$credentials = new \Didww\Credentials('YOUR_API_KEY', 'production');
\Didww\Configuration::configure($credentials, ['timeout' => 20]);
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
$countries = \Didww\Item\Country::all()->getData();
$country = \Didww\Item\Country::find('uuid')->getData();

// Regions
$regions = \Didww\Item\Region::all()->getData();

// Cities
$cities = \Didww\Item\City::all()->getData();

// Areas
$areas = \Didww\Item\Area::all()->getData();

// NANPA Prefixes
$prefixes = \Didww\Item\NanpaPrefix::all()->getData();

// POPs (Points of Presence)
$pops = \Didww\Item\Pop::all()->getData();

// DID Group Types
$types = \Didww\Item\DidGroupType::all()->getData();

// DID Groups (with stock keeping units)
$groups = \Didww\Item\DidGroup::all(['include' => 'stock_keeping_units'])->getData();

// Available DIDs
$available = \Didww\Item\AvailableDid::all([
    'include' => 'did_group.stock_keeping_units',
])->getData();

// Proof Types
$proofTypes = \Didww\Item\ProofType::all()->getData();

// Public Keys
$publicKeys = \Didww\Item\PublicKey::all()->getData();

// Requirements
$requirements = \Didww\Item\Requirement::all()->getData();

// Balance (singleton)
$balance = \Didww\Item\Balance::find()->getData();
```

### DIDs

```php
// List DIDs
$dids = \Didww\Item\Did::all()->getData();

// Update DID - assign trunk and capacity
$did = \Didww\Item\Did::find('uuid')->getData();
$did->setDescription('Updated');
$did->setCapacityLimit(20);
$did->setVoiceInTrunk(\Didww\Item\VoiceInTrunk::build('trunk-uuid'));
$did->save();
```

### Voice In Trunks

```php
use Didww\Enum\CliFormat;
use Didww\Enum\Codec;
use Didww\Enum\MediaEncryptionMode;
use Didww\Enum\SstRefreshMethod;
use Didww\Enum\TransportProtocol;

// Create SIP trunk
$sip = new \Didww\Item\Configuration\Sip([
    'host' => 'sip.example.com',
    'port' => 5060,
    'codec_ids' => \Didww\Item\Configuration\Base::getDefaultCodecIds(),
    'transport_protocol_id' => TransportProtocol::UDP,
    'sst_refresh_method_id' => SstRefreshMethod::INVITE,
    'media_encryption_mode' => MediaEncryptionMode::DISABLED,
    'rerouting_disconnect_code_ids' => \Didww\Item\Configuration\Base::getDefaultReroutingDisconnectCodeIds(),
]);

$trunk = new \Didww\Item\VoiceInTrunk();
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

### Voice In Trunk Groups

```php
$group = new \Didww\Item\VoiceInTrunkGroup();
$group->setName('Primary Group');
$group->setCapacityLimit(50);
$groupDocument = $group->save();
```

### Voice Out Trunks

```php
use Didww\Enum\OnCliMismatchAction;

$trunk = new \Didww\Item\VoiceOutTrunk();
$trunk->setName('My Outbound Trunk');
$trunk->setAllowedSipIps(['0.0.0.0/0']);
$trunk->setOnCliMismatchAction(OnCliMismatchAction::REPLACE_CLI);
$trunk->setDefaultDid(\Didww\Item\Did::build('did-uuid'));
$trunkDocument = $trunk->save();
```

### Orders

```php
use Didww\Enum\CallbackMethod;

// Order by SKU
$order = new \Didww\Item\Order([
    'allow_back_ordering' => true,
    'items' => [
        new \Didww\Item\OrderItem\Did(['sku_id' => 'sku-uuid', 'qty' => 2]),
    ],
]);
$order->setCallbackUrl('https://example.com/callback');
$order->setCallbackMethod(CallbackMethod::POST);
$orderDocument = $order->save();

// Order available DID
$order = new \Didww\Item\Order([
    'items' => [
        new \Didww\Item\OrderItem\AvailableDid([
            'sku_id' => 'sku-uuid',
            'available_did_id' => 'available-did-uuid',
        ]),
    ],
]);

// Order reserved DID
$order = new \Didww\Item\Order([
    'items' => [
        new \Didww\Item\OrderItem\ReservationDid([
            'sku_id' => 'sku-uuid',
            'did_reservation_id' => 'reservation-uuid',
        ]),
    ],
]);

// Order capacity
$order = new \Didww\Item\Order([
    'items' => [
        new \Didww\Item\OrderItem\Capacity([
            'capacity_pool_id' => 'pool-uuid',
            'qty' => 1,
        ]),
    ],
]);
```

### DID Reservations

```php
$reservation = new \Didww\Item\DidReservation();
$reservation->setDescription('Reserved for client');
$reservation->setAvailableDid(\Didww\Item\AvailableDid::build('available-did-uuid'));
$reservationDocument = $reservation->save();

// Delete reservation
$reservation->delete();
```

### Shared Capacity Groups

```php
$group = new \Didww\Item\SharedCapacityGroup();
$group->setName('Shared Group');
$group->setSharedChannelsCount(20);
$group->setCapacityPool(\Didww\Item\CapacityPool::build('pool-uuid'));
$groupDocument = $group->save();
```

### Identities

```php
use Didww\Enum\IdentityType;

$identity = new \Didww\Item\Identity([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'phone_number' => '12125551234',
    'identity_type' => IdentityType::PERSONAL,
]);
$identity->setCountry(\Didww\Item\Country::build('country-uuid'));
$identityDocument = $identity->save();
```

### Addresses

```php
$address = new \Didww\Item\Address();
$address->setCityName('New York');
$address->setPostalCode('10001');
$address->setAddress('123 Main St');
$address->setIdentity(\Didww\Item\Identity::build('identity-uuid'));
$address->setCountry(\Didww\Item\Country::build('country-uuid'));
$addressDocument = $address->save();
```

### Exports

```php
use Didww\Enum\ExportType;

$export = new \Didww\Item\Export();
$export->setExportType(ExportType::CDR_IN);
$export->setFilterYear('2025');
$export->setFilterMonth('01');
$exportDocument = $export->save();

// Download the export when completed
$export = $exportDocument->getData();
$export->download('/tmp/export.csv');
```

## Filtering, Sorting, and Pagination

```php
$regionsDocument = \Didww\Item\Region::all([
    'filter' => ['country.id' => 'uuid', 'name' => 'Arizona'],
    'include' => 'country',
    'sort' => 'name',
    'page' => ['size' => 25, 'number' => 1],
]);
$regions = $regionsDocument->getData();
```

## Enums

The SDK provides PHP 8.1+ backed enum classes for all API option fields (for example `CallbackMethod`, `IdentityType`, `OrderStatus`, `ExportType`, `CliFormat`, `OnCliMismatchAction`, `MediaEncryptionMode`, `TransportProtocol`, `Codec`, and more).

```php
use Didww\Enum\CallbackMethod;
use Didww\Enum\IdentityType;

$order = new \Didww\Item\Order();
$order->setCallbackMethod(CallbackMethod::POST);

$identity = new \Didww\Item\Identity();
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
| `OnCliMismatchAction` | `string` | `SEND_ORIGINAL_CLI`, `REJECT_CALL`, `REPLACE_CLI` |
| `DefaultDstAction` | `string` | `ALLOW_ALL`, `REJECT_ALL` |
| `VoiceOutTrunkStatus` | `string` | `ACTIVE`, `BLOCKED` |
| `Feature` | `string` | `VOICE_IN`, `VOICE_OUT`, `T38`, `SMS_IN`, `SMS_OUT` |
| `AreaLevel` | `string` | `WORLDWIDE`, `COUNTRY`, `AREA`, `CITY` |
| `Codec` | `int` | `TELEPHONE_EVENT(6)`, `G723(7)`, `G729(8)`, `PCMU(9)`, `PCMA(10)`, ... |
| `TransportProtocol` | `int` | `UDP(1)`, `TCP(2)`, `TLS(3)` |
| `RxDtmfFormat` | `int` | `RFC_2833(1)`, `SIP_INFO(2)`, `RFC_2833_OR_SIP_INFO(3)` |
| `TxDtmfFormat` | `int` | `DISABLED(0)`, `RFC_2833(1)`, `SIP_INFO_RELAY(2)`, `SIP_INFO_DTMF(4)` |
| `SstRefreshMethod` | `int` | `INVITE(1)`, `UPDATE(2)`, `UPDATE_FALLBACK_INVITE(3)` |
| `ReroutingDisconnectCode` | `int` | 47 SIP error codes (56-108, 1505) |

## File Encryption

The SDK provides an `Encrypt` utility for encrypting files before upload, using RSA-OAEP + AES-256-CBC (matching DIDWW's encryption requirements).

```php
$publicKeys = \Didww\Item\PublicKey::all()->getData();
$fingerprint = \Didww\Encrypt::calculateFingerprint($publicKeys);
$encrypted = \Didww\Encrypt::encrypt($fileContents, $publicKeys);
```

## Webhook Signature Validation

Validate incoming webhook callbacks from DIDWW using HMAC-SHA1 signature verification.

```php
$validator = new \Didww\Callback\RequestValidator('YOUR_API_KEY');

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
| Requirement | `Didww\Item\Requirement` | list, find |
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
| Export | `Didww\Item\Export` | list, find, create |
| Address | `Didww\Item\Address` | list, find, create, delete |
| AddressVerification | `Didww\Item\AddressVerification` | list, create |
| Identity | `Didww\Item\Identity` | list, find, create, delete |
| EncryptedFile | `Didww\Item\EncryptedFile` | list, find, delete |
| PermanentSupportingDocument | `Didww\Item\PermanentSupportingDocument` | create, delete |
| Proof | `Didww\Item\Proof` | create, delete |
| RequirementValidation | `Didww\Item\RequirementValidation` | create |

## Contributing

Bug reports and pull requests are welcome on GitHub at https://github.com/didww/didww-api-3-php-sdk

## License

The package is available as open source under the terms of the [MIT License](https://opensource.org/licenses/MIT).
