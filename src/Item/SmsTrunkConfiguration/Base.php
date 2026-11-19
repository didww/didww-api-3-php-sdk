<?php

namespace Didww\Item\SmsTrunkConfiguration;

use Didww\Traits\HasEnumAttributes;
use Didww\Traits\HasSafeAttributes;
use Didww\Traits\RedactsSensitiveAttributes;

/**
 * Delivery configuration of an SMS trunk.
 *
 * Values keep their JSON shape: objects such as `headers` or
 * `query_parameters` are held as \stdClass, so that an empty object is sent
 * back as `{}`. Numbers go through PHP's JSON decoder, so integers beyond
 * PHP_INT_MAX and decimal notation (`1.10`) are not kept exactly.
 * Pass null to a setter to send an explicit null.
 */
abstract class Base
{
    use HasSafeAttributes;
    use HasEnumAttributes;
    use RedactsSensitiveAttributes;

    /**
     * Attributes that are JSON objects: an array given for them is sent as
     * an object, `{}` when empty.
     */
    protected const OBJECT_ATTRIBUTES = [];

    /**
     * Attributes that are JSON arrays: an array given for them is sent as a
     * list, whatever its keys.
     */
    protected const LIST_ATTRIBUTES = [];

    protected $attributes = [];

    private ?\stdClass $emptyAttributes = null;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    abstract public function getType(): string;

    public function fill(array $attributes)
    {
        $objectAttributes = static::OBJECT_ATTRIBUTES;
        foreach ($attributes as $key => $value) {
            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            } elseif (is_array($value)) {
                $value = in_array($key, $objectAttributes, true)
                    ? (object) $value
                    : array_map(static fn ($v) => $v instanceof \BackedEnum ? $v->value : $v, $value);
                if (in_array($key, static::LIST_ATTRIBUTES, true)) {
                    $value = array_values($value);
                }
            }
            $attributes[$key] = $value;
        }
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * A copy of the attributes: change them with the setters, since a value
     * changed in place would not be sent.
     */
    public function getAttributes(): array
    {
        return self::copyOf($this->attributes);
    }

    /**
     * Serialises to JSON:API form. Server-generated credentials are left out,
     * so that a loaded configuration can be sent back unchanged.
     */
    public function toJsonApiArray(): array
    {
        $attributes = array_diff_key($this->attributes, array_flip(self::readOnlyAttributesOf($this)));

        return [
            'type' => $this->getType(),
            'attributes' => [] === $attributes ? ($this->emptyAttributes ??= new \stdClass()) : $attributes,
        ];
    }

    /**
     * @param array|\stdClass $data a `{type, attributes}` configuration object
     */
    public static function fromArray(array|\stdClass $data): self
    {
        $data = (array) $data;
        $type = $data['type'] ?? null;
        $attributes = (array) ($data['attributes'] ?? []);

        return match ($type) {
            'sms_to_email' => new SmsToEmail($attributes),
            'http_in' => new HttpIn($attributes),
            'http_out' => new HttpOut($attributes),
            'smpp_esme' => new SmppEsme($attributes),
            'smpp_smsc' => new SmppSmsc($attributes),
            default => new Generic($type, $attributes),
        };
    }

    protected function objectAttribute(string $key): ?array
    {
        $value = $this->attribute($key);

        return null === $value ? null : self::toPlainArray($value);
    }

    protected function setObjectAttribute(string $key, ?array $value): void
    {
        $this->attributes[$key] = null === $value ? null : (object) $value;
    }

    protected static function toPlainArray(mixed $value): mixed
    {
        if (!is_object($value) && !is_array($value)) {
            return $value;
        }

        return array_map(static fn ($v) => self::toPlainArray($v), (array) $value);
    }

    // $value with its \stdClass objects and arrays copied, so that a change made
    // in place to what a getter returns leaves the configuration unchanged.
    protected static function copyOf(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            $copy = new \stdClass();
            foreach ($value as $key => $item) {
                $copy->{$key} = self::copyOf($item);
            }

            return $copy;
        }

        return is_array($value) ? array_map(static fn ($item) => self::copyOf($item), $value) : $value;
    }

    private static function readOnlyAttributesOf(self $configuration): array
    {
        return match (true) {
            $configuration instanceof HttpOut => HttpOut::READ_ONLY_ATTRIBUTES,
            $configuration instanceof SmppEsme => SmppEsme::READ_ONLY_ATTRIBUTES,
            default => [],
        };
    }
}
