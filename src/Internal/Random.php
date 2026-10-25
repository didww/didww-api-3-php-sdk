<?php

namespace Didww\Internal;

/**
 * @internal
 */
final class Random
{
    // 2^53: every integer below it is exact in a float, so the quotient never rounds up to 1.
    private const RESOLUTION = 9007199254740992;

    private function __construct()
    {
        // Static helpers only; not meant to be instantiated.
    }

    /**
     * A uniformly distributed number in [0, 1), drawn from the CSPRNG.
     */
    public static function fraction(): float
    {
        return random_int(0, self::RESOLUTION - 1) / self::RESOLUTION;
    }
}
