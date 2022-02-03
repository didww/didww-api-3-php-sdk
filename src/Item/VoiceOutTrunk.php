<?php

namespace Didww\Item;

class VoiceOutTrunk extends BaseItem
{
    use \Didww\Traits\Fetchable;
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Deletable;

    protected $type = 'voice_out_trunks';

    public function getName(): string
    {
        return $this->attributes['name'];
    }

    public function setName(string $name)
    {
        $this->attributes['name'] = $name;
    }

    public function getAllowedSipIps(): array
    {
        return $this->attributes['name'];
    }

    public function setAllowedSipIps(array $allowedSipIps)
    {
        $this->attributes['allowed_sip_ips'] = $allowedSipIps;
    }

    public function getOnCliMismatchAction(): string
    {
        return $this->attributes['on_cli_mismatch_action'];
    }

    public function setOnCliMismatchAction(string $onCliMismatchAction)
    {
        $this->attributes['on_cli_mismatch_action'] = $onCliMismatchAction;
    }

    public function getAllowedRtpIps(): ?array
    {
        return $this->attributes['allowed_rtp_ips'];
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

    public function getStatus(): string
    {
        return $this->attributes['status'];
    }

    public function setStatus(string $status)
    {
        $this->attributes['status'] = $status;
    }

    public function getCapacityLimit(): string
    {
        return $this->attributes['capacity_limit'];
    }

    public function setCapacityLimit(string $capacityLimit)
    {
        $this->attributes['capacity_limit'] = $capacityLimit;
    }

    public function getThresholdAmount(): ?string
    {
        return $this->attributes['threshold_amount'];
    }

    public function setThresholdAmount(?string $thresholdAmount)
    {
        $this->attributes['threshold_amount'] = $thresholdAmount;
    }

    public function getMediaEncryptionMode(): string
    {
        return $this->attributes['media_encryption_mode'];
    }

    public function setMediaEncryptionMode(string $mediaEncryptionMode)
    {
        $this->attributes['media_encryption_mode'] = $mediaEncryptionMode;
    }

    public function getDefaultDstAction(): string
    {
        return $this->attributes['default_dst_action'];
    }

    public function setDefaultDstAction(string $defaultDstAction)
    {
        $this->attributes['default_dst_action'] = $defaultDstAction;
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
        return $this->attributes['callback_url'];
    }

    public function setCallbackUrl(?string $callbackUrl)
    {
        $this->attributes['callback_url'] = $callbackUrl;
    }

    public function getCreatedAt()
    {
        return new \DateTime($this->attributes['created_at']);
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

    /** @return array [
     * ]
     * 'name' => string
     * 'created_at' => string // creation timestamp
     * 'allowed_sip_ips' => array
     * 'on_cli_mismatch_action' => string
     * 'allowed_rtp_ips' => ?array
     * 'allow_any_did_as_cli' => bool
     * 'status' => string
     * 'capacity_limit' => ?integer
     * 'username' => ?string
     * 'password' => ?string
     * 'threshold_reached' => bool
     * 'threshold_amount' => ?string // amount of money allowed to spent in 24 hours
     * 'media_encryption_mode' => string
     * 'default_dst_action' => string
     * 'dst_prefixes' => array
     * 'force_symmetric_rtp' => bool
     * 'rtp_ping' => bool
     * 'callback_url' => ?string
     */
    public function getAttributes()
    {
        return parent::getAttributes();
    }


    public function voiceInTrunkGroup()
    {
        return $this->hasOne(VoiceInTrunkGroup::class);
    }

    public function setVoiceInTrunkGroup(VoiceInTrunkGroup $voiceInTrunkGroup)
    {
        $this->voiceInTrunkGroup()->associate($voiceInTrunkGroup);
    }

    protected function getWhiteListAttributesKeys()
    {
        return [
            'name',
            'allowed_sip_ips',
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
        ];
    }
}
