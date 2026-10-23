<?php

namespace Didww\Prices\Internal;

/**
 * Typed access to the attributes of a price lookup row. A value of the wrong
 * type, or a missing value that is not nullable, throws
 * \UnexpectedValueException.
 *
 * @internal
 */
final class Attributes
{
    private function __construct(private readonly string $id, private readonly array $attributes)
    {
    }

    /**
     * @param array<mixed> $resource a JSON:API resource object
     */
    public static function of(array $resource): self
    {
        if (!is_string($resource['id'] ?? null) || '' === $resource['id']) {
            throw new \UnexpectedValueException('price row has no id');
        }
        if (!is_array($resource['attributes'] ?? null)) {
            throw new \UnexpectedValueException(sprintf('price row %s has no attributes', $resource['id']));
        }

        return new self($resource['id'], $resource['attributes']);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function string(string $name): string
    {
        return $this->nullableString($name) ?? throw $this->invalid($name, 'a string');
    }

    public function nullableString(string $name): ?string
    {
        $value = $this->attributes[$name] ?? null;
        if (null !== $value && !is_string($value)) {
            throw $this->invalid($name, 'a string');
        }

        return $value;
    }

    public function int(string $name): int
    {
        return $this->nullableInt($name) ?? throw $this->invalid($name, 'an integer');
    }

    public function nullableInt(string $name): ?int
    {
        $value = $this->attributes[$name] ?? null;
        if (null !== $value && !is_int($value)) {
            throw $this->invalid($name, 'an integer');
        }

        return $value;
    }

    public function bool(string $name): bool
    {
        $value = $this->attributes[$name] ?? null;
        if (!is_bool($value)) {
            throw $this->invalid($name, 'a boolean');
        }

        return $value;
    }

    public function money(string $name): float
    {
        return $this->nullableMoney($name) ?? throw $this->invalid($name, 'a decimal');
    }

    // A decimal string such as "0.075000"; a JSON number is accepted too.
    public function nullableMoney(string $name): ?float
    {
        $value = $this->attributes[$name] ?? null;
        if (null === $value) {
            return null;
        }
        if (is_int($value) || is_float($value) || (is_string($value) && preg_match('/\A-?[0-9]+(\.[0-9]+)?\z/', $value))) {
            return (float) $value;
        }

        throw $this->invalid($name, 'a decimal');
    }

    public function nullableDateTime(string $name): ?\DateTimeImmutable
    {
        $value = $this->nullableString($name);
        if (null === $value) {
            return null;
        }
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw $this->invalid($name, 'a date-time');
        }
    }

    private function invalid(string $name, string $type): \UnexpectedValueException
    {
        return new \UnexpectedValueException(sprintf('price row %s: %s is not %s', $this->id, $name, $type));
    }
}
