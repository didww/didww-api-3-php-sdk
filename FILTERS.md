# DIDWW 2026-10-22 — Server-side filter reference

This is the canonical list of `filter[KEY]` query parameters accepted by each list endpoint of the DIDWW API at version `2026-10-22` — the default API version targeted by this SDK.

The SDK passes filters through as untyped string→string maps (e.g. `Didww\Item\Region::all(['filter' => ['country.id' => $uuid]])`), so this document is the authoritative source for which keys actually do something on the server.

Keys that already existed at version `2026-04-16` were verified live against the DIDWW API at that version. Keys added at version `2026-10-22` are taken from its published OpenAPI document.

Pass values URL-encoded as strings. Where the server accepts arrays, comma-separate the values.

Two other kinds of filters follow the list endpoints: the [`filters` of an export](#export-filters--filters-of-post-v3exports), which are attributes of the created export rather than query parameters, and the [filters of the price lookups](#price-lookups--get-v3lookupprices), which the SDK checks before it sends a request.

## AddressRequirement — `GET /v3/address_requirements`

- `filter[country.id]`
- `filter[number_type]`

## Address — `GET /v3/addresses`

- `filter[address]`
- `filter[address_contains]`
- `filter[area.id]`
- `filter[city.id]`
- `filter[city_name]`
- `filter[city_name_contains]`
- `filter[country.id]`
- `filter[description]`
- `filter[description_contains]`
- `filter[external_reference_id]`
- `filter[identity.external_reference_id]`
- `filter[identity.id]`
- `filter[postal_code]`
- `filter[postal_code_contains]`

## AddressVerification — `GET /v3/address_verifications`

- `filter[address.id]`
- `filter[address.identity.id]`
- `filter[external_reference_id]`
- `filter[reference]`
- `filter[reference_in]`
- `filter[status]`

## Area — `GET /v3/areas`

- `filter[country.id]`
- `filter[name]`

## AvailableDid — `GET /v3/available_dids`

- `filter[city.id]`
- `filter[country.id]`
- `filter[did_group.features]`
- `filter[did_group.id]`
- `filter[did_group.needs_registration]`
- `filter[did_group.number_type]`
- `filter[nanpa_prefix.id]`
- `filter[number_contains]`
- `filter[region.id]`

## CapacityPool — `GET /v3/capacity_pools`

- `filter[country.id]`
- `filter[has_unassigned_channels]`
- `filter[name]`

## City — `GET /v3/cities`

- `filter[area.id]`
- `filter[country.id]`
- `filter[is_available]`
- `filter[name]`
- `filter[region.id]`

## Country — `GET /v3/countries`

- `filter[is_available]`
- `filter[iso]`
- `filter[name]`
- `filter[prefix]`

## DidGroup — `GET /v3/did_groups`

- `filter[allow_additional_channels]`
- `filter[area_name]`
- `filter[available_dids_enabled]`
- `filter[city.id]`
- `filter[country.id]`
- `filter[features]`
- `filter[is_available]`
- `filter[is_demo]`
- `filter[is_metered]`
- `filter[meta.total_count_gteq]`
- `filter[nanpa_prefix.id]`
- `filter[nanpa_prefix.npanxx]`
- `filter[needs_registration]`
- `filter[number_type]`
- `filter[prefix]`
- `filter[region.id]`

## DidHistory — `GET /v3/did_history`

- `filter[action]`
- `filter[created_at_gteq]`
- `filter[created_at_lteq]`
- `filter[did_number]`
- `filter[method]`

## DidReservation — `GET /v3/did_reservations`

- `filter[description]`

## Did — `GET /v3/dids`

- `filter[address_verification.id]`
- `filter[awaiting_registration]`
- `filter[billing_cycles_count]`
- `filter[blocked]`
- `filter[capacity_pool.id]`
- `filter[city.id]`
- `filter[country.id]`
- `filter[description]`
- `filter[did_group.features]`
- `filter[did_group.id]`
- `filter[emergency_calling_service.id]`
- `filter[emergency_enabled]`
- `filter[number]`
- `filter[order.id]`
- `filter[order.reference]`
- `filter[region.id]`
- `filter[shared_capacity_group.id]`
- `filter[sms_trunk.id]`
- `filter[sms_trunk_group.id]`
- `filter[terminated]`
- `filter[voice_in_trunk.id]`
- `filter[voice_in_trunk_group.id]`

## EmergencyCallingService — `GET /v3/emergency_calling_services`

- `filter[address.id]`
- `filter[country.id]`
- `filter[identity.id]`
- `filter[name]`
- `filter[number_type]`
- `filter[reference]`
- `filter[status]`

## EmergencyRequirement — `GET /v3/emergency_requirements`

- `filter[country.id]`
- `filter[number_type]`

## EmergencyVerification — `GET /v3/emergency_verifications`

- `filter[emergency_calling_service.id]`
- `filter[external_reference_id]`
- `filter[status]`

## Export — `GET /v3/exports`

- `filter[created_at_gteq]`
- `filter[created_at_lteq]`
- `filter[export_type]`
- `filter[external_reference_id]`
- `filter[status]`

## Identity — `GET /v3/identities`

- `filter[birth_date]`
- `filter[company_name]`
- `filter[company_name_contains]`
- `filter[company_reg_number]`
- `filter[company_reg_number_contains]`
- `filter[company_website]`
- `filter[company_website_contains]`
- `filter[country.id]`
- `filter[description]`
- `filter[description_contains]`
- `filter[external_reference_id]`
- `filter[first_name]`
- `filter[first_name_contains]`
- `filter[id_number]`
- `filter[id_number_contains]`
- `filter[identity_type]`
- `filter[last_name]`
- `filter[last_name_contains]`
- `filter[personal_tax_id]`
- `filter[personal_tax_id_contains]`
- `filter[phone_number]`
- `filter[phone_number_contains]`
- `filter[vat_id]`
- `filter[vat_id_contains]`

## NanpaPrefix — `GET /v3/nanpa_prefixes`

- `filter[country.id]`
- `filter[did_group.features]`
- `filter[did_group.is_available]`
- `filter[npa]`
- `filter[npanxx]`
- `filter[nxx]`
- `filter[region.id]`

## Order — `GET /v3/orders`

- `filter[created_at_gteq]`
- `filter[created_at_lteq]`
- `filter[external_reference_id]`
- `filter[reference]`
- `filter[status]`

## PermanentSupportingDocument — `GET /v3/permanent_supporting_documents`

- `filter[external_reference_id]`

## Proof — `GET /v3/proofs`

- `filter[external_reference_id]`

## ProofType — `GET /v3/proof_types`

- `filter[entity_type]`

## Region — `GET /v3/regions`

- `filter[country.id]`
- `filter[iso]`
- `filter[name]`

## SenderIdVerification — `GET /v3/sender_id_verifications`

- `filter[name]` — contains
- `filter[reference]`
- `filter[sender_type]` — a `Didww\Enum\SenderType` value, e.g. `long_code`
- `filter[sms_trunk.id]` — comma-separate several
- `filter[status]` — a `Didww\Enum\SenderIdVerificationStatus` value, e.g. `changes_required`

## SharedCapacityGroup — `GET /v3/shared_capacity_groups`

- `filter[capacity_pool.id]`
- `filter[external_reference_id]`
- `filter[name]`

## SmsTrunkGroup — `GET /v3/sms_trunk_groups`

- `filter[external_reference_id]`
- `filter[name]`

## SmsTrunk — `GET /v3/sms_trunks`

- `filter[configuration.type]` — `sms_to_email`, `http_in`, `http_out`, `smpp_esme` or `smpp_smsc` (the `getType()` of the `Didww\Item\SmsTrunkConfiguration` classes); comma-separate several
- `filter[external_reference_id]`
- `filter[name]`

## SupportingDocumentTemplate — `GET /v3/supporting_document_templates`

- `filter[name]`
- `filter[name_contains]`
- `filter[permanent]`

## VoiceInTrunkGroup — `GET /v3/voice_in_trunk_groups`

- `filter[external_reference_id]`

## VoiceInTrunk — `GET /v3/voice_in_trunks`

- `filter[configuration.type]`
- `filter[external_reference_id]`
- `filter[name]`

## VoiceOutTrunk — `GET /v3/voice_out_trunks`

- `filter[allow_any_did_as_cli]`
- `filter[authentication_method.type]`
- `filter[default_did.id]`
- `filter[default_dst_action]`
- `filter[external_reference_id]`
- `filter[media_encryption_mode]`
- `filter[name]`
- `filter[name_contains]`
- `filter[on_cli_mismatch_action]`
- `filter[status]`
- `filter[threshold_reached]`

## Export filters — `filters` of `POST /v3/exports`

Set them with the `Didww\Item\Export` setters, or with `setFilter($key, $value)` for any key. `from` and `to` are required for every export type, as `YYYY-MM-DD HH:MM:SS` in UTC (`from` inclusive, `to` exclusive). Any key that the export type does not accept is rejected.

| Key | Export types | Setter |
|---|---|---|
| `from` | all | `setFilterFrom()` |
| `to` | all | `setFilterTo()` |
| `did_number` | `cdr_in` | `setFilterDidNumber()` |
| `source_number` | `cdr_in` | `setFilterSourceNumber()` |
| `source_number_out` | `cdr_in` | `setFilterSourceNumberOut()` |
| `source_country_iso` | `cdr_in` | `setFilterSourceCountryIso()` |
| `voice_out_trunk.id` | `cdr_out` | `setFilterVoiceOutTrunkId()` |
| `source` | `cdr_out` | `setFilterSource()` |
| `cli` | `cdr_out` | `setFilterCli()` |
| `destination` | `cdr_out` | `setFilterDestination()` |
| `source_address_eq` | `sms_in`, `sms_out` | `setFilterSourceAddressEq()` |
| `source_address_cont` | `sms_in`, `sms_out` | `setFilterSourceAddressCont()` |
| `destination_address_eq` | `sms_in`, `sms_out` | `setFilterDestinationAddressEq()` |
| `destination_address_cont` | `sms_in`, `sms_out` | `setFilterDestinationAddressCont()` |
| `status` | `sms_in` (`Success`, `Failed`, `Queued`), `sms_out` (`Routing Error`, `In Progress`, `Queued`, `Failed`, `Success`) | `setFilterStatus()` |
| `sms_id` | `sms_in`, `sms_out` | `setFilterSmsId()` |
| `sms_trunk.id` | `sms_in`, `sms_out` — one id or an array of ids | `setFilterSmsTrunkId()` |
| `trunk_type` | `sms_in` — one or an array of `Didww\Enum\ExportTrunkType` values | `setFilterTrunkType()` |
| `service_type` | `sms_out` — `a2p` or `p2p`, in any letter case | `setFilterServiceType()` |
| `sender_id_verification.id` | `sms_out` — one id or an array of ids | `setFilterSenderIdVerificationId()` |

## Price lookups — `GET /v3/…/lookup/prices`

Pass them to `lookup()` of a `Didww\Prices` accessor, without the `filter[…]` wrapper, e.g. `Didww\Prices::voiceIn()->lookup(['number' => '18005550123'])`. A missing or unknown filter throws `\InvalidArgumentException` before any request is sent; null and blank values are dropped. The formats of the values are checked by the server.

| Accessor | Path | Filters |
|---|---|---|
| `Prices::voiceIn()` | `/v3/lookup/prices` on the Voice IN host | `number` (required), `src_country_iso` |
| `Prices::voiceOut()` | `/v3/lookup/prices` on the Voice OUT host | `number` (required) |
| `Prices::smsOutA2p()` | `/v3/sms_out/a2p/lookup/prices` | `dst_number`, `dst_country_iso` — at least one |
| `Prices::smsOutP2p()` | `/v3/sms_out/p2p/lookup/prices` | `dst_number`, `dst_country_iso` — at least one |
| `Prices::smsOutOtp()` | `/v3/sms_out/otp/lookup/prices` | `dst_number`, `dst_country_iso` — at least one |
| `Prices::smsIn()` | `/v3/sms_in/lookup/prices` | `dst_number`, `dst_country_iso` — at least one |

- `number`, `dst_number` — 8 to 15 digits with a non-zero first digit, in international format without a `+` or a dialling prefix.
- `src_country_iso`, `dst_country_iso` — an upper-case ISO 3166-1 alpha-2 code.
