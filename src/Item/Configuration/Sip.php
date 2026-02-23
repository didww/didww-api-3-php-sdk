<?php

namespace Didww\Item\Configuration;

use Didww\Enum\Codec;
use Didww\Enum\MediaEncryptionMode;
use Didww\Enum\ReroutingDisconnectCode;
use Didww\Enum\RxDtmfFormat;
use Didww\Enum\SstRefreshMethod;
use Didww\Enum\StirShakenMode;
use Didww\Enum\TransportProtocol;
use Didww\Enum\TxDtmfFormat;

class Sip extends Base
{
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
        $val = $this->attributes['codec_ids'] ?? null;

        return null !== $val ? array_map(fn ($v) => Codec::from($v), $val) : null;
    }

    public function getRxDtmfFormatId(): ?RxDtmfFormat
    {
        $val = $this->attributes['rx_dtmf_format_id'] ?? null;

        return null !== $val ? RxDtmfFormat::from($val) : null;
    }

    public function getTxDtmfFormatId(): ?TxDtmfFormat
    {
        $val = $this->attributes['tx_dtmf_format_id'] ?? null;

        return null !== $val ? TxDtmfFormat::from($val) : null;
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
        $val = $this->attributes['rerouting_disconnect_code_ids'] ?? null;

        return null !== $val ? array_map(fn ($v) => ReroutingDisconnectCode::from($v), $val) : null;
    }

    public function getSstSessionExpires()
    {
        return $this->attributes['sst_session_expires'];
    }

    public function getSstRefreshMethodId(): ?SstRefreshMethod
    {
        $val = $this->attributes['sst_refresh_method_id'] ?? null;

        return null !== $val ? SstRefreshMethod::from($val) : null;
    }

    public function getTransportProtocolId(): ?TransportProtocol
    {
        $val = $this->attributes['transport_protocol_id'] ?? null;

        return null !== $val ? TransportProtocol::from($val) : null;
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
        $val = $this->attributes['media_encryption_mode'] ?? null;

        return null !== $val ? MediaEncryptionMode::from($val) : null;
    }

    public function getStirShakenMode(): ?StirShakenMode
    {
        $val = $this->attributes['stir_shaken_mode'] ?? null;

        return null !== $val ? StirShakenMode::from($val) : null;
    }

    public function getAllowedRtpIps(): ?array
    {
        return $this->attributes['allowed_rtp_ips'];
    }

    // ##

    public function setHost($newHost)
    {
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
        $this->attributes['codec_ids'] = array_map(
            fn ($v) => $v instanceof Codec ? $v->value : $v,
            $newCodecIds
        );
    }

    public function setRxDtmfFormatId(RxDtmfFormat|int $newRxDtmfFormatId)
    {
        $this->attributes['rx_dtmf_format_id'] = $newRxDtmfFormatId instanceof RxDtmfFormat ? $newRxDtmfFormatId->value : $newRxDtmfFormatId;
    }

    public function setTxDtmfFormatId(TxDtmfFormat|int $newTxDtmfFormatId)
    {
        $this->attributes['tx_dtmf_format_id'] = $newTxDtmfFormatId instanceof TxDtmfFormat ? $newTxDtmfFormatId->value : $newTxDtmfFormatId;
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
        $this->attributes['sst_refresh_method_id'] = $newSstRefreshMethodId instanceof SstRefreshMethod ? $newSstRefreshMethodId->value : $newSstRefreshMethodId;
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
        $this->attributes['rerouting_disconnect_code_ids'] = array_map(
            fn ($v) => $v instanceof ReroutingDisconnectCode ? $v->value : $v,
            $newReroutingDisconnectCodeIds
        );
    }

    public function setSstSessionExpires($newSstSessionExpires)
    {
        $this->attributes['sst_session_expires'] = $newSstSessionExpires;
    }

    public function setTransportProtocolId(TransportProtocol|int $newTransportProtocolId)
    {
        $this->attributes['transport_protocol_id'] = $newTransportProtocolId instanceof TransportProtocol ? $newTransportProtocolId->value : $newTransportProtocolId;
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
        $this->attributes['media_encryption_mode'] = $mediaEncryptionMode instanceof MediaEncryptionMode ? $mediaEncryptionMode->value : $mediaEncryptionMode;
    }

    public function setStirShakenMode(StirShakenMode|string $stirShakenMode)
    {
        $this->attributes['stir_shaken_mode'] = $stirShakenMode instanceof StirShakenMode ? $stirShakenMode->value : $stirShakenMode;
    }

    public function setAllowedRtpIps(?array $allowedRtpIps)
    {
        $this->attributes['allowed_rtp_ips'] = $allowedRtpIps;
    }
}
