# DIDWW v3.5 (2026-04-16) — Server-side filter reference

This is the canonical list of `filter[KEY]` query parameters accepted by each list endpoint of the DIDWW API at version `2026-04-16` — the default API version targeted by this SDK.

The SDK passes filters through as untyped string→string maps (e.g. `Repository::all(['filter' => ['country.id' => $uuid]])`), so this document is the authoritative source for which keys actually do something on the server.

Every key listed here was verified live against the DIDWW API at version `2026-04-16`.

Pass values URL-encoded as strings. Where the server accepts arrays, comma-separate the values.

## AddressRequirement — `GET /v3/address_requirements`

- `filter[country.id]`
- `filter[did_group_type.id]`

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
- `filter[did_group_type.id]`
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
- `filter[did_group_type.id]`
- `filter[features]`
- `filter[is_available]`
- `filter[is_metered]`
- `filter[meta.total_count_gteq]`
- `filter[nanpa_prefix.id]`
- `filter[nanpa_prefix.npanxx]`
- `filter[needs_registration]`
- `filter[prefix]`
- `filter[region.id]`

## DidGroupType — `GET /v3/did_group_types`

- `filter[name]`

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
- `filter[terminated]`
- `filter[voice_in_trunk.id]`
- `filter[voice_in_trunk_group.id]`


## EmergencyCallingService — `GET /v3/emergency_calling_services`

- `filter[address.id]`
- `filter[country.id]`
- `filter[did_group_type.id]`
- `filter[identity.id]`
- `filter[name]`
- `filter[reference]`
- `filter[status]`

## EmergencyRequirement — `GET /v3/emergency_requirements`

- `filter[country.id]`
- `filter[did_group_type.id]`

## EmergencyVerification — `GET /v3/emergency_verifications`

- `filter[emergency_calling_service.id]`
- `filter[external_reference_id]`
- `filter[status]`

## Export — `GET /v3/exports`

- `filter[external_reference_id]`

## Identity — `GET /v3/identities`

- `filter[birth_date]`
- `filter[company_name]`
- `filter[company_name_contains]`
- `filter[company_reg_number]`
- `filter[company_reg_number_contains]`
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

## SharedCapacityGroup — `GET /v3/shared_capacity_groups`

- `filter[capacity_pool.id]`
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
