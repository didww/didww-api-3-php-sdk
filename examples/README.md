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
| [`dids.php`](dids.php) | Lists DIDs and demonstrates DID updates. |
| [`trunks.php`](trunks.php) | Creates SIP and PSTN trunks with enum-based configuration, prints details. |
| [`channel_groups.php`](channel_groups.php) | Manages shared capacity groups. |
| [`orders_sku.php`](orders_sku.php) | Creates a DID order by SKU. |
| [`orders_available_dids.php`](orders_available_dids.php) | Orders an available DID. |
| [`orders_reservation_dids.php`](orders_reservation_dids.php) | Reserves a DID and places an order from that reservation. |
| [`orders_capacities.php`](orders_capacities.php) | Purchases capacity by creating a capacity order item. |
| [`upload_file.php`](upload_file.php) | Encrypts a file and uploads to `encrypted_files`. |

## Troubleshooting

If `DIDWW_API_KEY` is missing, examples fail fast with:

`Please provide an DIDWW API key!`
