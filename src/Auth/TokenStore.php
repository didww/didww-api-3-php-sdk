<?php

namespace Didww\Auth;

/**
 * Caches access tokens.
 *
 * Keys are opaque strings that already cover the token service URL, the API
 * key (hashed), the audience, the API version and the scope, so one store may
 * be shared by several configurations or processes. A store holds live bearer
 * tokens: protect it like the API key. A token is stored for the lifetime
 * granted by the token service; it is replaced when the API refuses it (401),
 * not ahead of its expiry.
 *
 * The SDK logs and tolerates exceptions thrown by a store, but it cannot
 * interrupt a call that hangs: implementations bound their own I/O, for
 * example with connect and read timeouts of a few seconds on a Redis client.
 */
interface TokenStore
{
    /**
     * Returns the token stored under $key, or null when there is none or it has expired.
     */
    public function get(string $key): ?AccessToken;

    /**
     * Stores $token under $key for $ttl seconds; a ttl of zero or less means no expiry.
     */
    public function set(string $key, AccessToken $token, int $ttl): void;

    /**
     * Removes the token stored under $key, if any.
     */
    public function delete(string $key): void;
}
