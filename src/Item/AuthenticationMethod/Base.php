<?php

namespace Didww\Item\AuthenticationMethod;

use Didww\Traits\HasSafeAttributes;

abstract class Base
{
    use HasSafeAttributes;

    protected $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    abstract public function getType(): string;

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
