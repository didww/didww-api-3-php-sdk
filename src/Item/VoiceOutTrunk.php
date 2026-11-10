<?php

namespace Didww\Item;

use Didww\Enum\DefaultDstAction;
use Didww\Enum\IceMode;
use Didww\Enum\MediaEncryptionMode;
use Didww\Enum\OnCliMismatchAction;
use Didww\Enum\RtcpFeedbackMode;
use Didww\Enum\RtcpMuxMode;
use Didww\Enum\VoiceOutCodec;
use Didww\Enum\VoiceOutTrunkStatus;
use Didww\Item\AuthenticationMethod\Base as AuthenticationMethodBase;
use Didww\Traits\Deletable;
use Didww\Traits\Fetchable;
use Didww\Traits\HasExternalReferenceId;
use Didww\Traits\Saveable;

class VoiceOutTrunk extends BaseItem
{
    use Fetchable;
    use Saveable;
    use Deletable;
    use HasExternalReferenceId;

    public static function getEndpoint(): string
    {
        return '/voice_out_trunks';
    }

    protected $type = 'voice_out_trunks';

    protected $visible = [
        'name',
        'on_cli_mismatch_action',
        'allowed_rtp_ips',
        'allow_any_did_as_cli',
        'status',
        'capacity_limit',
        'threshold_amount',
        'media_encryption_mode',
        'default_dst_action',
        'dst_prefixes',
        'force_symmetric_rtp',
        'rtp_ping',
        'callback_url',
        'authentication_method',
        'external_reference_id',
        'emergency_enable_all',
        'rtp_timeout',
        'codecs',
        'ice_mode',
        'rtcp_mux_mode',
        'rtcp_feedback_mode',
    ];

    public function getName(): string
    {
        return $this->attributes['name'];
    }

    public function setName(string $name)
    {
        $this->attributes['name'] = $name;
    }

    public function getOnCliMismatchAction(): OnCliMismatchAction
    {
        return $this->enumAttribute('on_cli_mismatch_action', OnCliMismatchAction::class);
    }

    public function setOnCliMismatchAction(OnCliMismatchAction|string $onCliMismatchAction)
    {
        $this->setEnumAttribute('on_cli_mismatch_action', $onCliMismatchAction);
    }

    public function getAllowedRtpIps(): ?array
    {
        return $this->attribute('allowed_rtp_ips');
    }

    public function setAllowedRtpIps(?array $allowedRtpIps)
    {
        $this->attributes['allowed_rtp_ips'] = $allowedRtpIps;
    }

    public function getAllowAnyDidAsCli(): bool
    {
        return $this->attributes['allow_any_did_as_cli'];
    }

    public function setAllowAnyDidAsCli(bool $allowAnyDidAsCli)
    {
        $this->attributes['allow_any_did_as_cli'] = $allowAnyDidAsCli;
    }

    public function getStatus(): VoiceOutTrunkStatus
    {
        return $this->enumAttribute('status', VoiceOutTrunkStatus::class);
    }

    public function setStatus(VoiceOutTrunkStatus|string $status)
    {
        $this->setEnumAttribute('status', $status);
    }

    public function getCapacityLimit(): string
    {
        return $this->attributes['capacity_limit'];
    }

    public function setCapacityLimit(string $capacityLimit)
    {
        $this->attributes['capacity_limit'] = $capacityLimit;
    }

    /** Maximum amount of money allowed to be spent in 24 hours. */
    public function getThresholdAmount(): ?string
    {
        return $this->attribute('threshold_amount');
    }

    public function setThresholdAmount(?string $thresholdAmount)
    {
        $this->attributes['threshold_amount'] = $thresholdAmount;
    }

    public function getMediaEncryptionMode(): MediaEncryptionMode
    {
        return $this->enumAttribute('media_encryption_mode', MediaEncryptionMode::class);
    }

    public function setMediaEncryptionMode(MediaEncryptionMode|string $mediaEncryptionMode)
    {
        $this->setEnumAttribute('media_encryption_mode', $mediaEncryptionMode);
    }

    public function getDefaultDstAction(): DefaultDstAction
    {
        return $this->enumAttribute('default_dst_action', DefaultDstAction::class);
    }

    public function setDefaultDstAction(DefaultDstAction|string $defaultDstAction)
    {
        $this->setEnumAttribute('default_dst_action', $defaultDstAction);
    }

    public function getDstPrefixes(): array
    {
        return $this->attributes['dst_prefixes'];
    }

    public function setDstPrefixes(array $dstPrefixes)
    {
        $this->attributes['dst_prefixes'] = $dstPrefixes;
    }

    public function getForceSymmetricRtp(): bool
    {
        return $this->attributes['force_symmetric_rtp'];
    }

    public function setForceSymmetricRtp(bool $forceSymmetricRtp)
    {
        $this->attributes['force_symmetric_rtp'] = $forceSymmetricRtp;
    }

    public function getRtpPing(): bool
    {
        return $this->attributes['rtp_ping'];
    }

    public function setRtpPing(bool $rtpPing)
    {
        $this->attributes['rtp_ping'] = $rtpPing;
    }

    public function getCallbackUrl(): ?string
    {
        return $this->attribute('callback_url');
    }

    public function setCallbackUrl(?string $callbackUrl)
    {
        $this->attributes['callback_url'] = $callbackUrl;
    }

    public function getAuthenticationMethod(): ?AuthenticationMethodBase
    {
        $data = $this->attribute('authentication_method');
        if (null === $data || $data instanceof AuthenticationMethodBase) {
            return $data;
        }

        if ($data instanceof \stdClass) {
            $data = json_decode(json_encode($data), true);
        }

        return AuthenticationMethodBase::fromArray($data);
    }

    public function setAuthenticationMethod(AuthenticationMethodBase $authenticationMethod)
    {
        $this->attributes['authentication_method'] = $authenticationMethod->toJsonApiArray();
    }

    public function isActive(): bool
    {
        return VoiceOutTrunkStatus::ACTIVE === $this->getStatus();
    }

    public function isBlocked(): bool
    {
        return VoiceOutTrunkStatus::BLOCKED === $this->getStatus();
    }

    public function getRtpTimeout(): ?int
    {
        return $this->attribute('rtp_timeout');
    }

    public function setRtpTimeout(?int $rtpTimeout)
    {
        $this->attributes['rtp_timeout'] = $rtpTimeout;
    }

    public function getEmergencyEnableAll(): ?bool
    {
        return $this->attribute('emergency_enable_all');
    }

    public function setEmergencyEnableAll(bool $emergencyEnableAll)
    {
        $this->attributes['emergency_enable_all'] = $emergencyEnableAll;
    }

    /**
     * Codecs offered on calls from the trunk, in priority order: the first has
     * the highest priority. Left out on create, the trunk offers `pcma`,
     * `pcmu` and `telephone_event`. A name this SDK does not know is returned
     * as the raw string. (API 2026-10-22).
     *
     * @return array<VoiceOutCodec|string>|null
     */
    public function getCodecs(): ?array
    {
        return $this->enumArrayAttribute('codecs', VoiceOutCodec::class);
    }

    /**
     * @param array<VoiceOutCodec|string> $codecs codec names in priority order, the first is the highest
     */
    public function setCodecs(array $codecs)
    {
        $this->setEnumArrayAttribute('codecs', array_values($codecs));
    }

    /**
     * ICE on calls from the trunk. Defaults to `accept`. (API 2026-10-22).
     */
    public function getIceMode(): IceMode|string|null
    {
        return $this->enumAttribute('ice_mode', IceMode::class);
    }

    public function setIceMode(IceMode|string $iceMode)
    {
        $this->setEnumAttribute('ice_mode', $iceMode);
    }

    /**
     * RTP and RTCP on one port on calls from the trunk. Defaults to `accept`.
     * (API 2026-10-22).
     */
    public function getRtcpMuxMode(): RtcpMuxMode|string|null
    {
        return $this->enumAttribute('rtcp_mux_mode', RtcpMuxMode::class);
    }

    public function setRtcpMuxMode(RtcpMuxMode|string $rtcpMuxMode)
    {
        $this->setEnumAttribute('rtcp_mux_mode', $rtcpMuxMode);
    }

    /**
     * RTCP feedback (AVPF) on calls from the trunk. Defaults to `accept`.
     * (API 2026-10-22).
     */
    public function getRtcpFeedbackMode(): RtcpFeedbackMode|string|null
    {
        return $this->enumAttribute('rtcp_feedback_mode', RtcpFeedbackMode::class);
    }

    public function setRtcpFeedbackMode(RtcpFeedbackMode|string $rtcpFeedbackMode)
    {
        $this->setEnumAttribute('rtcp_feedback_mode', $rtcpFeedbackMode);
    }

    public function getCreatedAt()
    {
        return $this->dateAttribute('created_at');
    }

    public function dids()
    {
        return $this->hasMany(Did::class);
    }

    public function setDids(\Swis\JsonApi\Client\Collection $dids)
    {
        $this->dids()->associate($dids);
    }

    public function emergencyDids()
    {
        return $this->hasMany(Did::class, 'emergency_dids');
    }

    public function setEmergencyDids(\Swis\JsonApi\Client\Collection $emergencyDids)
    {
        $this->emergencyDids()->associate($emergencyDids);
    }

    public function defaultDid()
    {
        return $this->hasOne(Did::class);
    }

    public function setDefaultDid(Did $defaultDid)
    {
        $this->defaultDid()->associate($defaultDid);
    }

    public function voiceInTrunkGroup()
    {
        return $this->hasOne(VoiceInTrunkGroup::class);
    }

    public function setVoiceInTrunkGroup(VoiceInTrunkGroup $voiceInTrunkGroup)
    {
        $this->voiceInTrunkGroup()->associate($voiceInTrunkGroup);
    }
}
