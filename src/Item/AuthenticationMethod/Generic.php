<?php

namespace Didww\Item\AuthenticationMethod;

/**
 * Forward-compatible wrapper for unknown authentication_method types.
 * If the server introduces new types, they will be wrapped in this class.
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
}
