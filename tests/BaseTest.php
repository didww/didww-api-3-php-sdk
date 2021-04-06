<?php

namespace Didww\Tests;

abstract class BaseTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
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
}
