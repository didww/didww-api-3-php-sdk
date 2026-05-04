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

    public function testSipConfigurationDebugInfoRedactsCredentials()
    {
        // var_dump / print_r call __debugInfo() to obtain the displayed
        // representation. None of those contexts should leak credentials.
        $secretPass = 's3cret-Pa55';
        $secretIncPass = 'srv-pass-xyz';
        $config = new \Didww\Item\Configuration\Sip([
            'username' => 'alice',
            'host' => 'sip.example.com',
            'auth_password' => $secretPass,
            'enabled_sip_registration' => true,
            'incoming_auth_username' => 'srv-user-xyz',
            'incoming_auth_password' => $secretIncPass,
        ]);

        $info = $config->__debugInfo();
        $attrs = $info['attributes'];
        $this->assertSame('alice', $attrs['username']);
        $this->assertSame('sip.example.com', $attrs['host']);
        $this->assertSame('[FILTERED]', $attrs['auth_password']);
        $this->assertSame('[FILTERED]', $attrs['incoming_auth_username']);
        $this->assertSame('[FILTERED]', $attrs['incoming_auth_password']);

        // Print-style dump of the whole object must not contain the secrets.
        $dump = print_r($config, true);
        $this->assertStringNotContainsString($secretPass, $dump);
        $this->assertStringNotContainsString($secretIncPass, $dump);
        $this->assertStringContainsString('[FILTERED]', $dump);

        // Wire format is unaffected.
        $payload = $config->toJsonApiArray();
        $this->assertSame($secretPass, $payload['attributes']['auth_password']);
    }
}
