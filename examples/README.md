# Examples

All examples read the API key from the `DIDWW_API_KEY` environment variable and use the sandbox environment unless `DIDWW_ENV` says otherwise.

Several examples create, change or order things on the account (trunks, orders, exports, DID routing). Run them against a sandbox account. The resources an example creates are deleted at the end where the API allows it; orders and exports cannot be deleted.

## Prerequisites

- PHP 8.2+
- Composer
- DIDWW API key for sandbox account

## Environment variables

- `DIDWW_API_KEY` (required): your DIDWW API key; `webhook.php` makes no API call and does not need it
- `DIDWW_ENV` (optional): `sandbox` (default) or `production`; `test_readme.php` runs against the sandbox only
- `DIDWW_API_URL`, `DIDWW_AUTH_URL`, `DIDWW_API_VIN_URL`, `DIDWW_API_VOUT_URL`, `DIDWW_API_SMS_URL` (optional): override the URL of the JSON:API, the token service and the Voice IN, Voice OUT and SMS prices services; [`bootstrap.php`](bootstrap.php) passes them to `Didww\Configuration::configure()`
- `FILE_PATH` (optional for `upload_file.php`): the file to upload, [`sample.pdf`](sample.pdf) if not set

## Install

The examples use the SDK of this repository. Install its dependencies in the repository root:

```bash
composer install
```

## Run an example

```bash
DIDWW_API_KEY=your_api_key php examples/balance.php
```

### Pass an argument

`prices_lookup.php` takes an optional number, `prices_download.php` an optional directory:

```bash
DIDWW_API_KEY=your_api_key php examples/prices_lookup.php 18005550123
```

## Available examples

| # | Script | Description |
|---|---|---|
| 1 | [`balance.php`](balance.php) | Fetches and prints the account balance and credit. |
| 2 | [`countries.php`](countries.php) | Lists countries filtered by prefix and fetches one country by ID. |
| 3 | [`regions.php`](regions.php) | Lists the regions of a country with the country included and fetches one region. |
| 4 | [`did_groups.php`](did_groups.php) | Lists DID groups with their SKUs included, with number_type and is_demo. |
| 5 | [`did_history.php`](did_history.php) | Lists DID ownership history events (last 90 days). |
| 6 | [`dids.php`](dids.php) | Lists the last ordered DIDs with their emergency relationships and updates the first one (capacity, capacity limit, description). |
| 7 | [`did_trunk_assignment.php`](did_trunk_assignment.php) | Assigns a DID to a new trunk, then to a new trunk group (exclusive: the trunk is cleared), and restores the DID's routing. |
| 8 | [`did_sms_routing.php`](did_sms_routing.php) | Routes a DID's inbound SMS to an SMS trunk, then to an SMS trunk group (exclusive), and restores the DID's SMS routing. |
| 9 | [`did_reservations.php`](did_reservations.php) | Reserves an available DID, lists and finds reservations, deletes the reservation. |
| 10 | [`capacity_pools.php`](capacity_pools.php) | Lists capacity pools with their shared capacity groups and qty-based pricings included, finds one pool. |
| 11 | [`shared_capacity_groups.php`](shared_capacity_groups.php) | Creates a shared capacity group in a capacity pool, lists, updates and deletes it. |
| 12 | [`orders.php`](orders.php) | Lists recent orders, orders a DID by SKU and cancels (deletes) that order. |
| 13 | [`orders_sku.php`](orders_sku.php) | Orders DIDs by SKU and quantity, with external_reference_id. |
| 14 | [`orders_available_dids.php`](orders_available_dids.php) | Orders a specific available DID. |
| 15 | [`orders_reservation_dids.php`](orders_reservation_dids.php) | Reserves a DID and orders it. |
| 16 | [`orders_nanpa.php`](orders_nanpa.php) | Orders a NANPA DID by NPA/NXX prefix. |
| 17 | [`orders_capacity.php`](orders_capacity.php) | Buys capacity channels in a capacity pool. |
| 18 | [`orders_all_item_types.php`](orders_all_item_types.php) | Places one order with the three DID item variants (by SKU and quantity, an available DID, a reserved DID); capacity cannot join a DID order. |
| 19 | [`orders_emergency.php`](orders_emergency.php) | Reads the server-created emergency orders among the recent orders and follows an emergency calling service to its order. |
| 20 | [`trunks.php`](trunks.php) | Lists Voice IN trunks with their group, finds one, creates a SIP and a PSTN trunk, updates and deletes both. |
| 21 | [`voice_in_trunk_groups.php`](voice_in_trunk_groups.php) | Creates two trunks and a trunk group with them, lists groups, renames the group and deletes it with its trunks. |
| 22 | [`voice_in_trunk_sip_registration.php`](voice_in_trunk_sip_registration.php) | End-to-end SIP registration flow: create with `enabled_sip_registration: true`, rename, disable by setting `host`, re-enable by toggling the flag. The SDK keeps the dependent fields (`host`, `port`, `use_did_in_ruri`) aligned with the server's validation rules automatically. |
| 23 | [`voice_out_trunks.php`](voice_out_trunks.php) | CRUD for Voice OUT trunks with the polymorphic authentication_method and codecs in priority order. Requires account configuration. |
| 24 | [`sms_trunks.php`](sms_trunks.php) | Lists SMS trunks, creates an HTTP IN trunk, updates its configuration, regenerates the credentials of an HTTP OUT trunk and deletes both. |
| 25 | [`sms_trunk_groups.php`](sms_trunk_groups.php) | Creates two inbound SMS trunks, groups them by priority, lists groups and deletes the group with its trunks. |
| 26 | [`sender_id_verifications.php`](sender_id_verifications.php) | Lists Sender ID verifications with their SMS trunks, renames an active one and routes it through an SMS trunk, then restores its name and trunk. |
| 27 | [`identity_address_proofs.php`](identity_address_proofs.php) | Creates an identity and an address, uploads an encrypted file, creates proofs for both, prints their verified flags and deletes what it created. |
| 28 | [`address_verifications.php`](address_verifications.php) | Lists address verifications with reject_comment and external_reference_id. |
| 29 | [`upload_file.php`](upload_file.php) | Encrypts a file with the DIDWW public keys, uploads it and prints the encrypted file ID. |
| 30 | [`exports.php`](exports.php) | Lists CDR exports and creates one with from/to filters and external_reference_id. |
| 31 | [`exports_sms.php`](exports_sms.php) | Creates SMS-In and SMS-Out exports with their filters and reads a completed one. |
| 32 | [`emergency_requirements.php`](emergency_requirements.php) | Lists emergency service requirements per country and number type. |
| 33 | [`emergency_requirement_validations.php`](emergency_requirement_validations.php) | Pre-validates an emergency order triple (requirement, address, identity). |
| 34 | [`emergency_verifications.php`](emergency_verifications.php) | Lists emergency verifications. |
| 35 | [`emergency_calling_services.php`](emergency_calling_services.php) | Lists emergency calling services. |
| 36 | [`emergency_scenario.php`](emergency_scenario.php) | End-to-end: order a DID, check requirements, validate, create a verification, get the service. |
| 37 | [`prices_lookup.php`](prices_lookup.php) | Looks up Voice IN, Voice OUT, SMS OUT (A2P, P2P, OTP) and SMS IN prices for a number. |
| 38 | [`prices_download.php`](prices_download.php) | Downloads the gzipped Voice IN price file to the directory given as argument, or to `didww-prices` in the user cache directory (created for the current user only), re-checks it with If-Modified-Since and reads a few rows. |
| 39 | [`includes.php`](includes.php) | Reads regions, DID groups, available DIDs and DIDs with included relationships, and shows a relationship that is not included. |
| 40 | [`webhook.php`](webhook.php) | Signs a sample callback the way DIDWW does and validates it with `RequestValidator`: `valid: true`, then `valid: false` for a tampered payload. |
| 41 | [`test_readme.php`](test_readme.php) | Runs the code snippets of the SDK README against the sandbox, one check per snippet, and prints `Results: N passed, M failed`. |

## Troubleshooting

If `DIDWW_API_KEY` is missing, examples fail fast with:

`Please provide an DIDWW API key!`
