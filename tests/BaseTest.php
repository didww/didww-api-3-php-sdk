<?php

namespace Didww\Tests;

abstract class BaseTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        \Didww\Configuration::configure($this->getDidwwCredentials(), [
            'timeout' => 20,
            'debug' => true,
        ]);

        parent::setUp();
    }

    protected function startVCR($cassetteName)
    {
        \VCR\VCR::turnOn();
        \VCR\VCR::insertCassette($cassetteName);
    }

    protected function stopVCR()
    {
        \VCR\VCR::eject();
        \VCR\VCR::turnOff();
    }

    protected function getDidwwCredentials(): \Didww\Credentials
    {
        return new \Didww\Credentials('PLACEYOURAPIKEYHERE', 'sandbox');
    }

    protected static function assertArraySubset(array $subset, array $array, string $message = ''): void
    {
        foreach ($subset as $key => $value) {
            static::assertArrayHasKey($key, $array, $message);
            if (is_array($value) && is_array($array[$key])) {
                static::assertArraySubset($value, $array[$key], $message);
            } else {
                static::assertEquals($value, $array[$key], $message);
            }
        }
    }
}
