<?php

namespace Didww\Item;

use Didww\Enum\DefaultDstAction;
use Didww\Enum\MediaEncryptionMode;
use Didww\Enum\OnCliMismatchAction;
use Didww\Enum\VoiceOutTrunkStatus;
use Didww\Item\AuthenticationMethod\Base as AuthenticationMethodBase;
use Didww\Traits\Deletable;
use Didww\Traits\Fetchable;
use Didww\Traits\Saveable;

class VoiceOutTrunk extends BaseItem
{
    use Fetchable;
    use Saveable;
    use Deletable;

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

    public function getExternalReferenceId(): ?string
    {
        return $this->attribute('external_reference_id');
    }

    public function setExternalReferenceId(?string $externalReferenceId)
    {
        $this->attributes['external_reference_id'] = $externalReferenceId;
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
