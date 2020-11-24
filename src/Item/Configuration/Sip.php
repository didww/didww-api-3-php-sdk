<?php

namespace Didww\Item\Configuration;

class Sip extends Base
{
    //todo: add getters setters
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

    public function getCodecIds()
    {
        return $this->attributes['codec_ids'];
    }

    public function getRxDtmfFormatId()
    {
        return $this->attributes['rx_dtmf_format_id'];
    }

    public function getTxDtmfFormatId()
    {
        return $this->attributes['tx_dtmf_format_id'];
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

    public function getReroutingDisconnectCodeIds()
    {
        return $this->attributes['rerouting_disconnect_code_ids'];
    }

    public function getSstSessionExpires()
    {
        return $this->attributes['sst_session_expires'];
    }

    public function getSstRefreshMethodId()
    {
        return $this->attributes['sst_refresh_method_id'];
    }

    public function getTransportProtocolId()
    {
        return $this->attributes['transport_protocol_id'];
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

    //##

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

    public function setCodecIds($newCodecIds)
    {
        $this->attributes['codec_ids'] = $newCodecIds;
    }

    public function setRxDtmfFormatId($newRxDtmfFormatId)
    {
        $this->attributes['rx_dtmf_format_id'] = $newRxDtmfFormatId;
    }

    public function setTxDtmfFormatId($newTxDtmfFormatId)
    {
        $this->attributes['tx_dtmf_format_id'] = $newTxDtmfFormatId;
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

    public function setSstRefreshMethodId($newSstRefreshMethodId)
    {
        $this->attributes['sst_refresh_method_id'] = $newSstRefreshMethodId;
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

    public function setReroutingDisconnectCodeIds($newReroutingDisconnectCodeIds)
    {
        $this->attributes['rerouting_disconnect_code_ids'] = $newReroutingDisconnectCodeIds;
    }

    public function setSstSessionExpires($newSstSessionExpires)
    {
        $this->attributes['sst_session_expires'] = $newSstSessionExpires;
    }

    public function setTransportProtocolId($newTransportProtocolId)
    {
        $this->attributes['transport_protocol_id'] = $newTransportProtocolId;
    }

    public function setMaxTransfers($newMaxTransfers)
    {
        $this->attributes['max_transfers'] = $newMaxTransfers;
    }

    public function setMax30xRedirects($newMax30xRedirects)
    {
        $this->attributes['max_30x_redirects'] = $newMax30xRedirects;
    }
}
