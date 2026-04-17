# Examples

All examples read the API key from the `DIDWW_API_KEY` environment variable.

## Prerequisites

- PHP 8.2+
- Composer
- DIDWW API key for sandbox account

## Environment variables

- `DIDWW_API_KEY` (required): your DIDWW API key

## Install

```bash
cd examples && composer install
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
| [`did_groups.php`](did_groups.php) | Fetches DID groups with included SKUs and shows group details. |
| [`dids.php`](dids.php) | Lists DIDs and demonstrates DID updates (2026-04-16 emergency fields). |
| [`did_history.php`](did_history.php) | Lists DID ownership history (last 90 days, 2026-04-16). |
| [`exports.php`](exports.php) | Creates and lists CDR exports, with 2026-04-16 external_reference_id. |
| [`trunks.php`](trunks.php) | Creates SIP and PSTN trunks with enum-based configuration, prints details. |
| [`voice_out_trunks.php`](voice_out_trunks.php) | CRUD for voice out trunks with 2026-04-16 polymorphic authentication_method. |
| [`voice_in_trunk_groups.php`](voice_in_trunk_groups.php) | CRUD for voice in trunk groups with 2026-04-16 external_reference_id. |
| [`channel_groups.php`](channel_groups.php) | Manages shared capacity groups with 2026-04-16 external_reference_id. |
| [`orders_sku.php`](orders_sku.php) | Creates a DID order by SKU. |
| [`orders_nanpa.php`](orders_nanpa.php) | Orders a DID number by NPA/NXX prefix. |
| [`orders_available_dids.php`](orders_available_dids.php) | Orders an available DID. |
| [`orders_reservation_dids.php`](orders_reservation_dids.php) | Reserves a DID and places an order from that reservation. |
| [`orders_capacities.php`](orders_capacities.php) | Purchases capacity by creating a capacity order item. |
| [`upload_file.php`](upload_file.php) | Encrypts a file and uploads to `encrypted_files`. |
| [`identities_and_proofs.php`](identities_and_proofs.php) | Lists identities, addresses, and proofs (2026-04-16 birth_country). |
| [`address_verifications.php`](address_verifications.php) | Lists address verifications with 2026-04-16 reject_comment / external_reference_id. |

### Emergency Services (2026-04-16)
| Script | Description |
|---|---|
| [`emergency_requirements.php`](emergency_requirements.php) | Lists emergency service requirements per country/did_group_type. |
| [`emergency_calling_services.php`](emergency_calling_services.php) | Lists and cancels customer emergency calling services. |
| [`emergency_verifications.php`](emergency_verifications.php) | Lists and creates emergency verifications. |
| [`emergency_requirement_validations.php`](emergency_requirement_validations.php) | Pre-validates an emergency order triple (requirement + address + identity). |
| [`orders_emergency.php`](orders_emergency.php) | Inspects server-created Emergency orders and `emergency_order_items`. |

## Troubleshooting

If `DIDWW_API_KEY` is missing, examples fail fast with:

`Please provide an DIDWW API key!`
