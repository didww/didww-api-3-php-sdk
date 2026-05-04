<?php

namespace Didww\Item\Configuration;

use Didww\Enum\Codec;
use Didww\Enum\DiversionInjectMode;
use Didww\Enum\DiversionRelayPolicy;
use Didww\Enum\MediaEncryptionMode;
use Didww\Enum\NetworkProtocolPriority;
use Didww\Enum\ReroutingDisconnectCode;
use Didww\Enum\RxDtmfFormat;
use Didww\Enum\SstRefreshMethod;
use Didww\Enum\StirShakenMode;
use Didww\Enum\TransportProtocol;
use Didww\Enum\TxDtmfFormat;

class Sip extends Base
{
    /**
     * Server-generated attributes returned in responses but not accepted on
     * write. Sending them in POST/PATCH triggers a `400 Param not allowed`
     * server error, so they are stripped from the JSON:API write payload by
     * `toJsonApiArray()`.
     */
    public const READ_ONLY_ATTRIBUTES = [
        'incoming_auth_username',
        'incoming_auth_password',
    ];

    protected array $sensitiveAttributes = [
        'auth_password',
        'incoming_auth_username',
        'incoming_auth_password',
    ];

    public function getType(): string
    {
        return 'sip_configurations';
    }

    public function getUsername()
    {
        return $this->attributes['username'];
    }

    public function getPort()
    {
        return $this->attributes['port'];
    }

    /**
     * @return Codec[]|null
     */
    public function getCodecIds(): ?array
    {
        return $this->enumArrayAttribute('codec_ids', Codec::class);
    }

    public function getRxDtmfFormatId(): ?RxDtmfFormat
    {
        return $this->enumAttribute('rx_dtmf_format_id', RxDtmfFormat::class);
    }

    public function getTxDtmfFormatId(): ?TxDtmfFormat
    {
        return $this->enumAttribute('tx_dtmf_format_id', TxDtmfFormat::class);
    }

    public function getResolveRuri()
    {
        return $this->attributes['resolve_ruri'];
    }

    public function getAuthEnabled()
    {
        return $this->attributes['auth_enabled'];
    }

    public function getAuthUser()
    {
        return $this->attributes['auth_user'];
    }

    public function getAuthPassword()
    {
        return $this->attributes['auth_password'];
    }

    public function getAuthFromUser()
    {
        return $this->attributes['auth_from_user'];
    }

    public function getAuthFromDomain()
    {
        return $this->attributes['auth_from_domain'];
    }

    public function getSstEnabled()
    {
        return $this->attributes['sst_enabled'];
    }

    public function getSstMinTimer()
    {
        return $this->attributes['sst_min_timer'];
    }

    public function getSstMaxTimer()
    {
        return $this->attributes['sst_max_timer'];
    }

    public function getSstAccept501()
    {
        return $this->attributes['sst_accept_501'];
    }

    public function getSipTimerB()
    {
        return $this->attributes['sip_timer_b'];
    }

    public function getDnsFailoverTimer()
    {
        return $this->attributes['dns_srv_failover_timer'];
    }

    public function getRtpPing()
    {
        return $this->attributes['rtp_ping'];
    }

    public function getForceSymmetricRtp()
    {
        return $this->attributes['force_symmetric_rtp'];
    }

    /**
     * @return ReroutingDisconnectCode[]|null
     */
    public function getReroutingDisconnectCodeIds(): ?array
    {
        return $this->enumArrayAttribute('rerouting_disconnect_code_ids', ReroutingDisconnectCode::class);
    }

    public function getSstSessionExpires()
    {
        return $this->attributes['sst_session_expires'];
    }

    public function getSstRefreshMethodId(): ?SstRefreshMethod
    {
        return $this->enumAttribute('sst_refresh_method_id', SstRefreshMethod::class);
    }

    public function getTransportProtocolId(): ?TransportProtocol
    {
        return $this->enumAttribute('transport_protocol_id', TransportProtocol::class);
    }

    public function getMaxTransfers()
    {
        return $this->attributes['max_transfers'];
    }

    public function getMax30xRedirects()
    {
        return $this->attributes['max_30x_redirects'];
    }

    public function getHost()
    {
        return $this->attributes['host'];
    }

    public function getMediaEncryptionMode(): ?MediaEncryptionMode
    {
        return $this->enumAttribute('media_encryption_mode', MediaEncryptionMode::class);
    }

    public function getStirShakenMode(): ?StirShakenMode
    {
        return $this->enumAttribute('stir_shaken_mode', StirShakenMode::class);
    }

    public function getAllowedRtpIps(): ?array
    {
        return $this->attribute('allowed_rtp_ips');
    }

    public function getDiversionRelayPolicy(): ?DiversionRelayPolicy
    {
        return $this->enumAttribute('diversion_relay_policy', DiversionRelayPolicy::class);
    }

    /**
     * Diversion header injection mode. (API 2026-04-16).
     */
    public function getDiversionInjectMode(): ?DiversionInjectMode
    {
        return $this->enumAttribute('diversion_inject_mode', DiversionInjectMode::class);
    }

    /**
     * SIP network protocol priority. (API 2026-04-16).
     */
    public function getNetworkProtocolPriority(): ?NetworkProtocolPriority
    {
        return $this->enumAttribute('network_protocol_priority', NetworkProtocolPriority::class);
    }

    /**
     * Whether SIP registration is enabled. When true the server generates
     * `incoming_auth_username` / `incoming_auth_password` and the trunk's
     * `host` and `port` must be left blank. When disabling sip registration
     * on an existing trunk, the same PATCH must also set `host` to a
     * non-blank value and `use_did_in_ruri` to false, or the server returns
     * 422. (API 2026-04-16).
     */
    public function getEnabledSipRegistration()
    {
        return $this->attributes['enabled_sip_registration'] ?? null;
    }

    /**
     * When true, the trunk's R-URI uses the DID number. Requires
     * `enabled_sip_registration` to be true. (API 2026-04-16).
     */
    public function getUseDidInRuri()
    {
        return $this->attributes['use_did_in_ruri'] ?? null;
    }

    /**
     * Enables CNAM resolution for inbound calls on this trunk. (API 2026-04-16).
     */
    public function getCnamLookup()
    {
        return $this->attributes['cnam_lookup'] ?? null;
    }

    /**
     * Server-generated SIP authentication username. Returned in responses when
     * `enabled_sip_registration` is true. Read-only — the API rejects any
     * write attempt with `400 Param not allowed`. (API 2026-04-16).
     */
    public function getIncomingAuthUsername()
    {
        return $this->attributes['incoming_auth_username'] ?? null;
    }

    /**
     * Server-generated SIP authentication password. Returned in responses when
     * `enabled_sip_registration` is true. Read-only — the API rejects any
     * write attempt with `400 Param not allowed`. (API 2026-04-16).
     */
    public function getIncomingAuthPassword()
    {
        return $this->attributes['incoming_auth_password'] ?? null;
    }

    // ##

    /**
     * Setting host to a non-null value cascades enabled_sip_registration
     * and use_did_in_ruri to false because the server requires those
     * states whenever host is present (API 2026-04-16). Constructor /
     * fill() bypass this setter, so deserialized server responses keep
     * their original combinations.
     */
    public function setHost($newHost)
    {
        if (null !== $newHost) {
            $this->attributes['enabled_sip_registration'] = false;
            $this->attributes['use_did_in_ruri'] = false;
        }
        $this->attributes['host'] = $newHost;
    }

    public function setUsername($newUserName)
    {
        $this->attributes['username'] = $newUserName;
    }

    public function setPort($newPort)
    {
        $this->attributes['port'] = $newPort;
    }

    /**
     * @param array<Codec|int> $newCodecIds
     */
    public function setCodecIds(array $newCodecIds)
    {
        $this->setEnumArrayAttribute('codec_ids', $newCodecIds);
    }

    public function setRxDtmfFormatId(RxDtmfFormat|int $newRxDtmfFormatId)
    {
        $this->setEnumAttribute('rx_dtmf_format_id', $newRxDtmfFormatId);
    }

    public function setTxDtmfFormatId(TxDtmfFormat|int $newTxDtmfFormatId)
    {
        $this->setEnumAttribute('tx_dtmf_format_id', $newTxDtmfFormatId);
    }

    public function setResolveRuri($newResolveRuri)
    {
        $this->attributes['resolve_ruri'] = $newResolveRuri;
    }

    public function setAuthEnabled($newAuthEnabled)
    {
        $this->attributes['auth_enabled'] = $newAuthEnabled;
    }

    public function setAuthUser($newAuthUser)
    {
        $this->attributes['auth_user'] = $newAuthUser;
    }

    public function setAuthPassword($newAuthPassword)
    {
        $this->attributes['auth_password'] = $newAuthPassword;
    }

    public function setAuthFromUser($newAuthFromUser)
    {
        $this->attributes['auth_from_user'] = $newAuthFromUser;
    }

    public function setAuthFromDomain($newAuthFromDomain)
    {
        $this->attributes['auth_from_domain'] = $newAuthFromDomain;
    }

    public function setSstEnabled($newSstEnabled)
    {
        $this->attributes['sst_enabled'] = $newSstEnabled;
    }

    public function setSstRefreshMethodId(SstRefreshMethod|int $newSstRefreshMethodId)
    {
        $this->setEnumAttribute('sst_refresh_method_id', $newSstRefreshMethodId);
    }

    public function setSstMinTimer($newSstMinTimer)
    {
        $this->attributes['sst_min_timer'] = $newSstMinTimer;
    }

    public function setSstMaxTimer($newSstMaxTimer)
    {
        $this->attributes['sst_max_timer'] = $newSstMaxTimer;
    }

    public function setSstAccept501($newSstAccept501)
    {
        $this->attributes['sst_accept_501'] = $newSstAccept501;
    }

    public function setSipTimerB($newSipTimerB)
    {
        $this->attributes['sip_timer_b'] = $newSipTimerB;
    }

    public function setDnsFailoverTimer($newDnsFailoverTimer)
    {
        $this->attributes['dns_srv_failover_timer'] = $newDnsFailoverTimer;
    }

    public function setRtpPing($newRtpPing)
    {
        $this->attributes['rtp_ping'] = $newRtpPing;
    }

    public function setForceSymmetricRtp($newForceSymmetricRtp)
    {
        $this->attributes['force_symmetric_rtp'] = $newForceSymmetricRtp;
    }

    /**
     * @param array<ReroutingDisconnectCode|int> $newReroutingDisconnectCodeIds
     */
    public function setReroutingDisconnectCodeIds(array $newReroutingDisconnectCodeIds)
    {
        $this->setEnumArrayAttribute('rerouting_disconnect_code_ids', $newReroutingDisconnectCodeIds);
    }

    public function setSstSessionExpires($newSstSessionExpires)
    {
        $this->attributes['sst_session_expires'] = $newSstSessionExpires;
    }

    public function setTransportProtocolId(TransportProtocol|int $newTransportProtocolId)
    {
        $this->setEnumAttribute('transport_protocol_id', $newTransportProtocolId);
    }

    public function setMaxTransfers($newMaxTransfers)
    {
        $this->attributes['max_transfers'] = $newMaxTransfers;
    }

    public function setMax30xRedirects($newMax30xRedirects)
    {
        $this->attributes['max_30x_redirects'] = $newMax30xRedirects;
    }

    public function setMediaEncryptionMode(MediaEncryptionMode|string $mediaEncryptionMode)
    {
        $this->setEnumAttribute('media_encryption_mode', $mediaEncryptionMode);
    }

    public function setStirShakenMode(StirShakenMode|string $stirShakenMode)
    {
        $this->setEnumAttribute('stir_shaken_mode', $stirShakenMode);
    }

    public function setAllowedRtpIps(?array $allowedRtpIps)
    {
        $this->attributes['allowed_rtp_ips'] = $allowedRtpIps;
    }

    public function setDiversionRelayPolicy(DiversionRelayPolicy|string $diversionRelayPolicy)
    {
        $this->setEnumAttribute('diversion_relay_policy', $diversionRelayPolicy);
    }

    public function setDiversionInjectMode(DiversionInjectMode|string $diversionInjectMode)
    {
        $this->setEnumAttribute('diversion_inject_mode', $diversionInjectMode);
    }

    public function setNetworkProtocolPriority(NetworkProtocolPriority|string $networkProtocolPriority)
    {
        $this->setEnumAttribute('network_protocol_priority', $networkProtocolPriority);
    }

    /**
     * Setting enabled_sip_registration cascades dependent fields:
     *   - true  -> nullify host / port and emit them as null on the wire
     *     (host/port must be blank when sip_registration is on). The wire
     *     emission fires unconditionally so PATCH against an existing trunk
     *     that already has host/port set server-side is told to clear them.
     *   - false -> force use_did_in_ruri = false (must be disabled when
     *     sip_registration is disabled).
     */
    public function setEnabledSipRegistration(bool $enabledSipRegistration)
    {
        if (true === $enabledSipRegistration) {
            // Always emit host: null and port: null on the wire so a PATCH
            // against an existing trunk that already has them persisted on
            // the server side is told to clear them.
            $this->attributes['host'] = null;
            $this->attributes['port'] = null;
        } else {
            $this->attributes['use_did_in_ruri'] = false;
        }
        $this->attributes['enabled_sip_registration'] = $enabledSipRegistration;
    }

    public function setUseDidInRuri(bool $useDidInRuri)
    {
        $this->attributes['use_did_in_ruri'] = $useDidInRuri;
    }

    public function setCnamLookup(bool $cnamLookup)
    {
        $this->attributes['cnam_lookup'] = $cnamLookup;
    }

    /**
     * @return DiversionInjectMode[]
     */
    public static function getDiversionInjectModes(): array
    {
        return DiversionInjectMode::cases();
    }

    /**
     * @return NetworkProtocolPriority[]
     */
    public static function getNetworkProtocolPriorities(): array
    {
        return NetworkProtocolPriority::cases();
    }

    /**
     * Serialise to JSON:API form. Read-only attributes (server-generated SIP
     * registration credentials) are stripped from the payload so that
     * round-tripping a loaded configuration through PATCH does not echo them
     * back and trigger `400 Param not allowed`.
     */
    public function toJsonApiArray(): array
    {
        $payload = parent::toJsonApiArray();

        if (isset($payload['attributes']) && is_array($payload['attributes'])) {
            foreach (self::READ_ONLY_ATTRIBUTES as $key) {
                unset($payload['attributes'][$key]);
            }
        }

        return $payload;
    }
}
