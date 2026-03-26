<?php

namespace Didww\Traits;

trait HasEnumAttributes
{
    protected function enumAttribute(string $key, string $enumClass): ?\BackedEnum
    {
        $val = $this->attribute($key);

        return null !== $val ? $enumClass::from($val) : null;
    }

    protected function setEnumAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value instanceof \BackedEnum ? $value->value : $value;
    }

    protected function setEnumArrayAttribute(string $key, array $values): void
    {
        $this->attributes[$key] = array_map(
            fn ($v) => $v instanceof \BackedEnum ? $v->value : $v,
            $values
        );
    }

    protected function enumArrayAttribute(string $key, string $enumClass): ?array
    {
        $val = $this->attribute($key);

        return null !== $val ? array_map(fn ($v) => $enumClass::from($v), $val) : null;
    }

    protected function dateAttribute(string $key): ?\DateTime
    {
        $val = $this->attribute($key);

        return null !== $val ? new \DateTime($val) : null;
    }
}
