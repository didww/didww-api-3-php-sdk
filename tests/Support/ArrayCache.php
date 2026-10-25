<?php

namespace Didww\Tests\Support;

use Psr\SimpleCache\CacheInterface;

/**
 * An in-memory PSR-16 cache that records the TTLs it was given.
 */
class ArrayCache implements CacheInterface
{
    public array $values = [];
    public array $ttls = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        if (preg_match('#[{}()/\\\\@:]#', $key) || strlen($key) > 64) {
            throw new \InvalidArgumentException('invalid key '.$key);
        }
        $this->values[$key] = $value;
        $this->ttls[$key] = $ttl;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->values[$key], $this->ttls[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->values = [];
        $this->ttls = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }
}
