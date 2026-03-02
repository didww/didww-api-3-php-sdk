<?php

namespace Didww\Tests;

class CredentialsTest extends \PHPUnit\Framework\TestCase
{
    public function testDefaultApiVersion()
    {
        $credentials = new \Didww\Credentials('PLACEYOURAPIKEYHERE', 'sandbox');

        $this->assertEquals('2022-05-10', $credentials->getVersion());
    }

    public function testCustomApiVersion()
    {
        $credentials = new \Didww\Credentials('PLACEYOURAPIKEYHERE', 'sandbox', '2021-12-15');

        $this->assertEquals('2021-12-15', $credentials->getVersion());
    }
}
