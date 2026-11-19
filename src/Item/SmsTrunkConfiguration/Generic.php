<?php

namespace Didww\Item\SmsTrunkConfiguration;

/**
 * A configuration of a type this SDK does not know, or one built by hand to
 * send exactly the given attributes, explicit nulls included. Debug output
 * masks every value. Pass a \stdClass for a JSON object that may be empty.
 */
class Generic extends Base
{
    private string $type;

    public function __construct(?string $type, array $attributes = [])
    {
        $this->type = $type ?? 'unknown';
        parent::__construct($attributes);
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * A copy of the value: change it with setAttribute(), since a value changed
     * in place would not be sent.
     */
    public function getAttribute(string $key): mixed
    {
        return self::copyOf($this->attribute($key));
    }

    public function setAttribute(string $key, mixed $value)
    {
        $this->attributes[$key] = $value instanceof \BackedEnum ? $value->value : $value;
    }

    public function __debugInfo(): array
    {
        return [
            'type' => $this->getType(),
            'attributes' => array_map(static fn ($value) => null === $value ? null : '[FILTERED]', $this->attributes),
        ];
    }
}
