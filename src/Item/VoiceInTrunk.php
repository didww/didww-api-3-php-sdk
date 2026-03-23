<?php

namespace Didww\Item;

use Didww\Enum\CliFormat;
use Didww\Item\Configuration\Pstn;
use Didww\Item\Configuration\Sip;
use Didww\Traits\Deletable;
use Didww\Traits\Fetchable;
use Didww\Traits\Saveable;

class VoiceInTrunk extends BaseItem
{
    use Fetchable;
    use Saveable;
    use Deletable;

    protected $type = 'voice_in_trunks';

    /**
     * The priority of this target host. DIDWW will attempt to contact the target trunk with the
     * lowest-numbered priority; trunks with the same priority are tried in an order defined by
     * the weight field. Range: 0-65535. See RFC 2782 for more details.
     */
    public function getPriority(): int
    {
        return $this->attributes['priority'];
    }

    public function setPriority(int $priority)
    {
        $this->attributes['priority'] = $priority;
    }

    /**
     * A trunk selection mechanism. Larger weights give a proportionately higher probability of
     * being selected among trunks with the same priority. Range: 0-65535. Records with weight 0
     * are rarely selected when higher-weight records exist. See RFC 2782 for more details.
     */
    public function getWeight(): int
    {
        return $this->attributes['weight'];
    }

    public function setWeight(int $weight)
    {
        $this->attributes['weight'] = $weight;
    }

    /**
     * CLI format conversion. May not work correctly for calls originating outside the DID's country.
     * Possible values: RAW (do not alter CLI), 164 (convert to E.164), Local (convert to localized format).
     */
    public function getCliFormat(): CliFormat
    {
        return $this->enumAttribute('cli_format', CliFormat::class);
    }

    public function setCliFormat(CliFormat|string $cliFormat)
    {
        $this->setEnumAttribute('cli_format', $cliFormat);
    }

    /**
     * Optional CLI prefix. You may prefix with an optional '+' sign followed by up to 6 characters
     * including digits and '#'.
     */
    public function getCliPrefix(): string
    {
        return $this->attributes['cli_prefix'];
    }

    public function setCliPrefix(string $cliPrefix)
    {
        $this->attributes['cli_prefix'] = $cliPrefix;
    }

    public function setDescription(string $description)
    {
        $this->attributes['description'] = $description;
    }

    /** Optional description of the trunk. */
    public function getDescription(): string
    {
        return $this->attributes['description'];
    }

    /** Maximum seconds to wait for answer before ending the call with disconnect code 'Ringing timeout'. */
    public function getRingingTimeout(): int
    {
        return $this->attributes['ringing_timeout'];
    }

    public function setRingingTimeout(int $ringingTimeout)
    {
        $this->attributes['ringing_timeout'] = $ringingTimeout;
    }

    public function getName(): string
    {
        return $this->attributes['name'];
    }

    public function setName(string $name)
    {
        $this->attributes['name'] = $name;
    }

    public function getCreatedAt()
    {
        return $this->dateAttribute('created_at');
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function setConfiguration(Configuration\Base $configuration)
    {
        $this->configuration = $configuration;
    }

    public function pop()
    {
        return $this->hasOne(Pop::class);
    }

    public function voiceInTrunkGroup()
    {
        return $this->hasOne(VoiceInTrunkGroup::class);
    }

    private const CONFIGURATION_CLASSES = [
        'sip' => Sip::class,
        'pstn' => Pstn::class,
    ];

    public static function configurationFactory(string $type)
    {
        $class = self::CONFIGURATION_CLASSES[$type] ?? throw new \InvalidArgumentException("Unknown configuration type: $type");

        return new $class();
    }

    public function setVoiceInTrunkGroup(VoiceInTrunkGroup $voiceInTrunkGroup)
    {
        $this->voiceInTrunkGroup()->associate($voiceInTrunkGroup);
    }

    public function setPop(Pop $pop)
    {
        $this->pop()->associate($pop);
    }

    public function toJsonApiArray(): array
    {
        $data = parent::toJsonApiArray();
        if ($this->configuration) {
            $data['attributes']['configuration'] = $this->configuration->toJsonApiArray();
        }

        return $data;
    }

    public function fill(array $attributes)
    {
        if (isset($attributes['configuration'])) {
            $this->fillConfiguration($attributes['configuration']);
            unset($attributes['configuration']);
        }

        return parent::fill($attributes);
    }

    private function fillConfiguration($configuration)
    {
        if (is_array($configuration)) {
            $this->configuration = $this->buildConfiguration($configuration['type']);
            $this->configuration->fill($configuration['attributes']);
        } elseif ($configuration instanceof Configuration\Base) {
            $this->configuration = $configuration;
        } elseif (is_object($configuration)) {
            $this->configuration = $this->buildConfiguration($configuration->type);
            $this->configuration->fill((array) $configuration->attributes);
        } else {
            throw new \InvalidArgumentException('can\'t set configuration');
        }
    }

    private function buildConfiguration($type)
    {
        $parts = explode('_configurations', $type);

        return self::configurationFactory($parts[0]);
    }

    protected function getWhiteListAttributesKeys()
    {
        return [
            'priority',
            'capacity_limit',
            'weight',
            'name',
            'cli_format',
            'cli_prefix',
            'description',
            'ringing_timeout',
            'configuration',
        ];
    }
}
