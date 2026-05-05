<?php

namespace Didww\Traits;

/**
 * Provides a `__debugInfo()` override that masks values of attribute
 * keys listed in `$sensitiveAttributes` so credentials never leak
 * through default `var_dump` / `print_r` / unhandled exception
 * representations. The wire payload is unaffected — `toJsonApiArray()`
 * (or whatever serializer the host class uses) still emits the real
 * values.
 *
 * Used by `Item\Configuration\Base` and `Item\AuthenticationMethod\Base`,
 * which both expose an `$attributes` array and a `getType()` method.
 *
 * Subclasses that include this trait extend `$sensitiveAttributes`:
 *
 *     protected array $sensitiveAttributes = ['password', 'username'];
 */
trait RedactsSensitiveAttributes
{
    /**
     * Attribute names whose values are credentials.
     *
     * @var string[]
     */
    protected array $sensitiveAttributes = [];

    public function __debugInfo(): array
    {
        $masked = [];
        foreach ($this->attributes as $key => $value) {
            $masked[$key] = (in_array($key, $this->sensitiveAttributes, true) && null !== $value)
                ? '[FILTERED]'
                : $value;
        }

        return ['type' => $this->getType(), 'attributes' => $masked];
    }
}
