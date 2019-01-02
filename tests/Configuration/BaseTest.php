<?php

namespace Didww\Tests\Configuration;

class BaseTest extends \PHPUnit\Framework\TestCase
{
    public function testGetRxDtmfFormats()
    {
        $options = \Didww\Item\Configuration\Base::getRxDtmfFormats();
        $this->assertEquals(count($options), 3);
    }

    public function testGetTxDtmfFormats()
    {
        $options = \Didww\Item\Configuration\Base::getTxDtmfFormats();
        $this->assertEquals(count($options), 4);
    }

    public function testGetSstRefreshMethods()
    {
        $options = \Didww\Item\Configuration\Base::getSstRefreshMethods();
        $this->assertEquals(count($options), 3);
    }

    public function testGetTransportProtocols()
    {
        $options = \Didww\Item\Configuration\Base::getTransportProtocols();
        $this->assertEquals(count($options), 2);
    }

    public function testGetReroutingDisconnectCodes()
    {
        $options = \Didww\Item\Configuration\Base::getReroutingDisconnectCodes();
        $this->assertEquals(count($options), 47);
    }

    public function testGetCodecs()
    {
        $options = \Didww\Item\Configuration\Base::getCodecs();
        $this->assertEquals(count($options), 13);
    }

    public function testGetDefaultReroutingDisconnectCodeIds()
    {
        $options = \Didww\Item\Configuration\Base::getDefaultReroutingDisconnectCodeIds();
        $this->assertEquals(count($options), 45);
    }

    public function testGetDefaultCodecIds()
    {
        $options = \Didww\Item\Configuration\Base::getDefaultCodecIds();
        $this->assertEquals(count($options), 5);
    }
}
