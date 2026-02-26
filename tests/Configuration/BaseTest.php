<?php

namespace Didww\Tests\Configuration;

use Didww\Enum\Codec;
use Didww\Enum\MediaEncryptionMode;
use Didww\Enum\ReroutingDisconnectCode;
use Didww\Enum\RxDtmfFormat;
use Didww\Enum\SstRefreshMethod;
use Didww\Enum\StirShakenMode;
use Didww\Enum\TransportProtocol;
use Didww\Enum\TxDtmfFormat;

class BaseTest extends \PHPUnit\Framework\TestCase
{
    public function testGetRxDtmfFormats()
    {
        $options = \Didww\Item\Configuration\Base::getRxDtmfFormats();
        $this->assertCount(3, $options);
        $this->assertContainsOnlyInstancesOf(RxDtmfFormat::class, $options);
    }

    public function testGetTxDtmfFormats()
    {
        $options = \Didww\Item\Configuration\Base::getTxDtmfFormats();
        $this->assertCount(4, $options);
        $this->assertContainsOnlyInstancesOf(TxDtmfFormat::class, $options);
    }

    public function testGetSstRefreshMethods()
    {
        $options = \Didww\Item\Configuration\Base::getSstRefreshMethods();
        $this->assertCount(3, $options);
        $this->assertContainsOnlyInstancesOf(SstRefreshMethod::class, $options);
    }

    public function testGetTransportProtocols()
    {
        $options = \Didww\Item\Configuration\Base::getTransportProtocols();
        $this->assertCount(3, $options);
        $this->assertContainsOnlyInstancesOf(TransportProtocol::class, $options);
    }

    public function testGetReroutingDisconnectCodes()
    {
        $options = \Didww\Item\Configuration\Base::getReroutingDisconnectCodes();
        $this->assertCount(47, $options);
        $this->assertContainsOnlyInstancesOf(ReroutingDisconnectCode::class, $options);
    }

    public function testGetCodecs()
    {
        $options = \Didww\Item\Configuration\Base::getCodecs();
        $this->assertCount(13, $options);
        $this->assertContainsOnlyInstancesOf(Codec::class, $options);
    }

    public function testGetDefaultReroutingDisconnectCodeIds()
    {
        $options = \Didww\Item\Configuration\Base::getDefaultReroutingDisconnectCodeIds();
        $this->assertCount(45, $options);
        $this->assertContainsOnlyInstancesOf(ReroutingDisconnectCode::class, $options);
    }

    public function testGetDefaultCodecIds()
    {
        $options = \Didww\Item\Configuration\Base::getDefaultCodecIds();
        $this->assertCount(5, $options);
        $this->assertContainsOnlyInstancesOf(Codec::class, $options);
    }

    public function testGetMediaEncryptionModes()
    {
        $options = \Didww\Item\Configuration\Base::getMediaEncryptionModes();
        $this->assertCount(4, $options);
        $this->assertContainsOnlyInstancesOf(MediaEncryptionMode::class, $options);
    }

    public function testGetStirShakenModes()
    {
        $options = \Didww\Item\Configuration\Base::getStirShakenModes();
        $this->assertCount(5, $options);
        $this->assertContainsOnlyInstancesOf(StirShakenMode::class, $options);
    }
}
