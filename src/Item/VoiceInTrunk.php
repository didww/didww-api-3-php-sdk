<?php

namespace Didww\Item;

class VoiceInTrunk extends BaseItem
{
    use \Didww\Traits\Fetchable;
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Deletable;

    protected $type = 'voice_in_trunks';

    public function getPriority(): int
    {
        return $this->attributes['priority'];
    }

    public function setPriority(int $priority)
    {
        $this->attributes['priority'] = $priority;
    }

    public function getWeight(): int
    {
        return $this->attributes['weight'];
    }

    public function setWeight(int $weight)
    {
        $this->attributes['weight'] = $weight;
    }

    public function getCliFormat(): string
    {
        return $this->attributes['cli_format'];
    }

    public function setCliFormat(string $cliFormat)
    {
        $this->attributes['cli_format'] = $cliFormat;
    }

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

    public function getDescription(): string
    {
        return $this->attributes['description'];
    }

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
        return new \DateTime($this->attributes['created_at']);
    }

    /** @return array [
     * ]
     * 'priority' => integer // The priority of this target host. DIDWW will attempt to contact the target trunk with the lowest-numbered priority; target trunk with the same priority will be tried in an order defined by the weight field. The range is 0-65535. See RFC 2782 for more details
     * 'weight' => integer // A trunk selection mechanism. The weight field specifies a relative weight for entries with the same priority. Larger weights will be given a proportionately higher probability of being selected. The range of this number is 0-65535. In the presence of records containing weights greater than 0, records with weight 0 will have a very small chance of being selected. See RFC 2782 for more details
     * 'capacity_limit' => integer //Maximum number of simultaneous calls for the trunk
     * 'ringing_timeout' => integer// After which it will be end transaction with internal disconnect code 'Ringing timeout' if the call was not connected.
     * 'name' => string // Friendly name of the trunk
     * 'cli_format'=> string  // CLI format conversion may not work correctly for phone calls originating from outside the country of that specific DID, Possible values: RAW - Do not alter CLI (default),  164 - Attempt to convert CLI to E.164 format,  Local - Attempt to convert CLI to Localized format
     * 'cli_prefix' => string // You may prefix the CLI with an optional '+' sign followed by up to 6 characters, including digits and '#'
     * 'description' => string // Optional description of trunk
     * 'created_at' => string // creation timestamp
     * 'configuration' => Didww\Configuration\Base instance
     */
    public function getAttributes()
    {
        return parent::getAttributes();
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

    public static function configurationFactory(string $type)
    {
        $class = '\\Didww\\Item\\Configuration\\'.ucfirst($type);

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
