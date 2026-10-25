# Examples

All examples read the API key from the `DIDWW_API_KEY` environment variable and use the sandbox environment unless `DIDWW_ENV` says otherwise.

Several examples create, change or order things on the account (trunks, orders, exports, DID routing). Run them against a sandbox account.

## Prerequisites

- PHP 8.2+
- Composer
- DIDWW API key for sandbox account

## Environment variables

- `DIDWW_API_KEY` (required): your DIDWW API key
- `DIDWW_ENV` (optional): `sandbox` (default) or `production`
- `DIDWW_API_URL`, `DIDWW_AUTH_URL`, `DIDWW_API_VIN_URL`, `DIDWW_API_VOUT_URL`, `DIDWW_API_SMS_URL` (optional): override the URL of the JSON:API, the token service and the Voice IN, Voice OUT and SMS prices services; [`bootstrap.php`](bootstrap.php) passes them to `Didww\Configuration::configure()`

## Install

The examples use the SDK of this repository. Install its dependencies in the repository root:

```bash
composer install
```

## Run an example

```bash
DIDWW_API_KEY=your_api_key php examples/balance.php
```

## Available examples

| Script | Description |
|---|---|
| [`balance.php`](balance.php) | Fetches and prints current account balance and credit. |
| [`countries.php`](countries.php) | Lists countries, demonstrates filtering, and fetches one country by ID. |
| [`regions.php`](regions.php) | Lists regions with includes and pagination. |
| [`did_groups.php`](did_groups.php) | Fetches DID groups with included SKUs and shows group details, including number_type and is_demo. |
| [`dids.php`](dids.php) | Lists DIDs and demonstrates DID updates (emergency fields). |
| [`did_history.php`](did_history.php) | Lists DID ownership history (last 90 days). |
| [`exports.php`](exports.php) | Creates and lists CDR exports, with external_reference_id. |
| [`exports_sms.php`](exports_sms.php) | Creates SMS-In and SMS-Out exports with their filters and reads a completed one. |
| [`trunks.php`](trunks.php) | Creates SIP and PSTN trunks with enum-based configuration, prints details including the POP. |
| [`voice_in_trunk_sip_registration.php`](voice_in_trunk_sip_registration.php) | End-to-end SIP registration flow: create with `enabled_sip_registration: true`, rename, disable by setting `host`, re-enable by toggling the flag. The SDK keeps the dependent fields (`host`, `port`, `use_did_in_ruri`) aligned with the server's validation rules automatically. |
| [`voice_out_trunks.php`](voice_out_trunks.php) | CRUD for voice out trunks with the polymorphic authentication_method. |
| [`voice_in_trunk_groups.php`](voice_in_trunk_groups.php) | CRUD for voice in trunk groups with external_reference_id. |
| [`channel_groups.php`](channel_groups.php) | Manages shared capacity groups with external_reference_id. |
| [`shared_capacity_groups.php`](shared_capacity_groups.php) | Lists shared capacity groups and the capacity pools they belong to, with external_reference_id. |
| [`orders_sku.php`](orders_sku.php) | Creates a DID order by SKU. |
| [`orders_nanpa.php`](orders_nanpa.php) | Orders a DID number by NPA/NXX prefix. |
| [`orders_available_dids.php`](orders_available_dids.php) | Orders an available DID. |
| [`orders_reservation_dids.php`](orders_reservation_dids.php) | Reserves a DID and places an order from that reservation. |
| [`orders_capacities.php`](orders_capacities.php) | Purchases capacity by creating a capacity order item. |
| [`upload_file.php`](upload_file.php) | Encrypts a file and uploads to `encrypted_files`. |
| [`identities_and_proofs.php`](identities_and_proofs.php) | Lists identities, addresses, and proofs (birth_country, company_website). |
| [`address_verifications.php`](address_verifications.php) | Lists address verifications with reject_comment / external_reference_id. |

### SMS
| Script | Description |
|---|---|
| [`sms_trunks.php`](sms_trunks.php) | Lists SMS trunks, creates an HTTP IN trunk, updates its configuration, regenerates the credentials of an HTTP OUT trunk and deletes both. |
| [`sms_trunk_groups.php`](sms_trunk_groups.php) | Creates two inbound SMS trunks, groups them by priority, lists groups and deletes the group with its trunks. |
| [`sender_id_verifications.php`](sender_id_verifications.php) | Lists Sender ID verifications with their SMS trunks, renames an active one and routes it through an SMS trunk that sends messages, then restores its name and original trunk, also when a step fails. |
| [`did_sms_routing.php`](did_sms_routing.php) | Routes a DID's inbound SMS to an SMS trunk, then to an SMS trunk group (exclusive assignment), and restores the DID's original SMS routing; the trunk and the group are created and deleted by the script. |

### Prices
| Script | Description |
|---|---|
| [`prices_lookup.php`](prices_lookup.php) | Looks up Voice IN, Voice OUT, SMS OUT (A2P, P2P, OTP) and SMS IN prices for a number. |
| [`prices_download.php`](prices_download.php) | Downloads the gzipped Voice IN price file to the directory given as argument, or to `didww-prices` in the user cache directory (created for the current user only), re-checks it with If-Modified-Since and reads a few rows. |

### Emergency Services
| Script | Description |
|---|---|
| [`emergency_requirements.php`](emergency_requirements.php) | Lists emergency service requirements per country/number_type. |
| [`emergency_calling_services.php`](emergency_calling_services.php) | Lists customer emergency calling services. |
| [`emergency_verifications.php`](emergency_verifications.php) | Lists and creates emergency verifications. |
| [`emergency_requirement_validations.php`](emergency_requirement_validations.php) | Pre-validates an emergency order triple (requirement + address + identity). |
| [`orders_emergency.php`](orders_emergency.php) | Inspects server-created Emergency orders and `emergency_order_items`. |
| [`emergency_scenario.php`](emergency_scenario.php) | End-to-end: find DID → check requirements → validate → create verification → get service. |

## Troubleshooting

If `DIDWW_API_KEY` is missing, examples fail fast with:

`Please provide an DIDWW API key!`
