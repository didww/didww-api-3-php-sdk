<?php

namespace Didww\Tests\Internal;

use Didww\Internal\Random;
use PHPUnit\Framework\TestCase;

class RandomTest extends TestCase
{
    public function testFractionIsInTheUnitInterval()
    {
        $values = [];
        for ($i = 0; $i < 1000; ++$i) {
            $value = Random::fraction();
            $this->assertGreaterThanOrEqual(0.0, $value);
            $this->assertLessThan(1.0, $value);
            $values[] = $value;
        }

        $this->assertGreaterThan(900, count(array_unique($values)));
    }

    public function testResolutionIsANativeInteger()
    {
        $resolution = (new \ReflectionMethod(Random::class, 'resolution'))->invoke(null);

        $this->assertIsInt($resolution);
        $this->assertSame(PHP_INT_SIZE >= 8 ? 9007199254740992 : 1073741824, $resolution);
    }
}
