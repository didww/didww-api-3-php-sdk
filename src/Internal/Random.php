<?php

namespace Didww\Internal;

/**
 * @internal
 */
final class Random
{
    private function __construct()
    {
        // Static helpers only; not meant to be instantiated.
    }

    /**
     * A uniformly distributed number in [0, 1), drawn from the CSPRNG.
     */
    public static function fraction(): float
    {
        $resolution = self::resolution();

        return random_int(0, $resolution - 1) / $resolution;
    }

    // A power of two that is a native integer: 2^53 (every integer below it
    // is exact in a float, so the quotient never rounds up to 1) on 64-bit
    // builds, 2^30 on 32-bit ones.
    private static function resolution(): int
    {
        return PHP_INT_SIZE >= 8 ? 1 << 53 : 1 << 30;
    }
}
