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
}
