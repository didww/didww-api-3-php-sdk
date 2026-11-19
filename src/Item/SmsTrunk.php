<?php

namespace Didww\Item;

use Didww\Item\SmsTrunkConfiguration\Base as SmsTrunkConfigurationBase;
use Didww\Traits\Deletable;
use Didww\Traits\Fetchable;
use Didww\Traits\HasExternalReferenceId;
use Didww\Traits\Saveable;

/**
 * An SMS trunk: how messages for the assigned DIDs are delivered, or
 * accepted for sending.
 *
 * On update the configuration type must match the stored one; attributes
 * left out of the configuration keep their values.
 *
 * Server-side filters: id, name, external_reference_id, configuration.type.
 * Includes: outgoing_dids, incoming_dids, sms_trunk_group, sender_id_verifications.
 */
class SmsTrunk extends BaseItem
{
    use Fetchable;
    use Saveable;
    use Deletable;
    use HasExternalReferenceId;

    public static function getEndpoint(): string
    {
        return '/sms_trunks';
    }

    protected $type = 'sms_trunks';

    protected $visible = [
        'name',
        'external_reference_id',
        'blocked',
        'priority',
        'allow_any_did_as_source',
        'configuration',
    ];

    public function getName(): string
    {
        return $this->attributes['name'];
    }

    public function setName(string $name)
    {
        $this->attributes['name'] = $name;
    }

    public function getBlocked(): ?bool
    {
        return $this->attribute('blocked');
    }

    /**
     * True stops the trunk from handling messages.
     */
    public function setBlocked(bool $blocked)
    {
        $this->attributes['blocked'] = $blocked;
    }

    public function getPriority(): ?int
    {
        return $this->attribute('priority');
    }

    /**
     * Routing priority within an SMS trunk group, 0-32767; the higher value is
     * tried first. Defaults to 100, and must stay null on `http_out` trunks.
     */
    public function setPriority(?int $priority)
    {
        $this->attributes['priority'] = $priority;
    }

    public function getAllowAnyDidAsSource(): ?bool
    {
        return $this->attribute('allow_any_did_as_source');
    }

    /**
     * Allows any DID of the customer as the message source. Turning it on
     * empties outgoing_dids. Refused on the trunk types that only receive.
     */
    public function setAllowAnyDidAsSource(bool $allowAnyDidAsSource)
    {
        $this->attributes['allow_any_did_as_source'] = $allowAnyDidAsSource;
    }

    public function getAllowIn(): ?bool
    {
        return $this->attribute('allow_in');
    }

    public function getAllowOut(): ?bool
    {
        return $this->attribute('allow_out');
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->dateAttribute('created_at');
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->dateAttribute('updated_at');
    }

    public function getConfiguration(): ?SmsTrunkConfigurationBase
    {
        return $this->attribute('configuration');
    }

    public function setConfiguration(SmsTrunkConfigurationBase $configuration)
    {
        $this->attributes['configuration'] = $configuration;
    }

    /**
     * The URL to submit outbound messages to (meta `host`); `http_out` trunks only.
     */
    public function getHost(): ?string
    {
        $meta = $this->getMeta();

        return $meta ? ($meta->toArray()['host'] ?? null) : null;
    }

    public function outgoingDids()
    {
        return $this->hasMany(Did::class, 'outgoing_dids');
    }

    /**
     * DIDs the trunk may send from. An empty collection clears them.
     */
    public function setOutgoingDids(\Swis\JsonApi\Client\Collection $outgoingDids)
    {
        $this->outgoingDids()->associate($outgoingDids);
    }

    public function incomingDids()
    {
        return $this->hasMany(Did::class, 'incoming_dids');
    }

    public function smsTrunkGroup()
    {
        return $this->hasOne(SmsTrunkGroup::class);
    }

    /**
     * Null removes the trunk from its group. `http_out` trunks cannot join a group.
     */
    public function setSmsTrunkGroup(?SmsTrunkGroup $smsTrunkGroup)
    {
        if (null === $smsTrunkGroup) {
            $this->smsTrunkGroup()->dissociate();
        } else {
            $this->smsTrunkGroup()->associate($smsTrunkGroup);
        }
    }

    public function senderIdVerifications()
    {
        return $this->hasMany(SenderIdVerification::class);
    }

    /**
     * Regenerates the credentials of an `smpp_esme` or `http_out` trunk. The
     * new credentials are read back from the trunk.
     */
    public function regenerateCredentials()
    {
        $credential = new SmsTrunkRegenerateCredential();
        $credential->setSmsTrunk(static::build($this->getId()));

        return $credential->save();
    }

    public function toJsonApiArray(): array
    {
        $data = parent::toJsonApiArray();
        $configuration = $this->attribute('configuration');
        if ($configuration instanceof SmsTrunkConfigurationBase) {
            $data['attributes']['configuration'] = $configuration->toJsonApiArray();
        }

        return $data;
    }

    public function fill(array $attributes)
    {
        if (array_key_exists('configuration', $attributes)) {
            $configuration = $attributes['configuration'];
            if (is_array($configuration) || $configuration instanceof \stdClass) {
                $attributes['configuration'] = SmsTrunkConfigurationBase::fromArray($configuration);
            } elseif (null !== $configuration && !$configuration instanceof SmsTrunkConfigurationBase) {
                throw new \InvalidArgumentException('can\'t set configuration');
            }
        }

        return parent::fill($attributes);
    }

    /**
     * Debug output without credentials: the configuration is shown through
     * its own redacted representation, and the persisted snapshot is left out.
     */
    public function __debugInfo(): array
    {
        $attributes = $this->attributes;
        if (($attributes['configuration'] ?? null) instanceof SmsTrunkConfigurationBase) {
            $attributes['configuration'] = $attributes['configuration']->__debugInfo();
        }

        return [
            'type' => $this->getType(),
            'id' => $this->getId(),
            'attributes' => $attributes,
            'meta' => $this->getMeta()?->toArray(),
        ];
    }
}
