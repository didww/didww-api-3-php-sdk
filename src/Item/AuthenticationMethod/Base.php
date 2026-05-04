<?php

namespace Didww\Item\AuthenticationMethod;

use Didww\Traits\HasSafeAttributes;

abstract class Base
{
    use HasSafeAttributes;

    protected $attributes = [];

    /**
     * Attribute names whose values are credentials. The wire format is
     * unchanged — toJsonApiArray() still emits the real values — but
     * __debugInfo() redacts these so var_dump / print_r / default error
     * reports never leak the credential downstream. Subclasses extend this.
     *
     * @var string[]
     */
    protected array $sensitiveAttributes = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    abstract public function getType(): string;

    /**
     * Custom var_dump / print_r output: redact sensitive attribute values
     * so credentials never leak through default debugging surfaces.
     */
    public function __debugInfo(): array
    {
        $masked = [];
        foreach ($this->attributes as $key => $value) {
            $masked[$key] = (in_array($key, $this->sensitiveAttributes, true) && $value !== null)
                ? '[FILTERED]'
                : $value;
        }
        return ['type' => $this->getType(), 'attributes' => $masked];
    }

    public function fill(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function toJsonApiArray(): array
    {
        return [
            'type' => $this->getType(),
            'attributes' => $this->attributes,
        ];
    }

    public static function fromArray(array $data): self
    {
        $type = $data['type'] ?? null;
        $attributes = $data['attributes'] ?? [];

        return match ($type) {
            'ip_only' => new IpOnly($attributes),
            'credentials_and_ip' => new CredentialsAndIp($attributes),
            'twilio' => new Twilio($attributes),
            default => new Generic($type, $attributes),
        };
    }
}
