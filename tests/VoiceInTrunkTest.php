<?php

namespace Didww\Tests;

use Didww\Enum\CliFormat;
use Didww\Enum\MediaEncryptionMode;
use Didww\Enum\RxDtmfFormat;
use Didww\Enum\SstRefreshMethod;
use Didww\Enum\StirShakenMode;
use Didww\Enum\TransportProtocol;
use Didww\Enum\TxDtmfFormat;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

class VoiceInTrunkTest extends CassetteTest
{
    use ArraySubsetAsserts;

    protected function getCassetteName(): string
    {
        return 'voice_in_trunks.yml';
    }

    public function testAllWithIncludesAndPagination()
    {
        $voiceInTrunksDocument = \Didww\Item\VoiceInTrunk::all(['sort' => '-created_at', 'include' => 'trunk_group,pop', 'page' => ['size' => 4, 'number' => 1]]);
        $voiceInTrunks = $voiceInTrunksDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\VoiceInTrunk', $voiceInTrunks);
        $voiceInTrunkGroup = $voiceInTrunks[0]->voiceInTrunkGroup()->getIncluded();

        $pop = $voiceInTrunks[1]->pop()->getIncluded();

        $this->assertInstanceOf('Didww\Item\Pop', $pop);
        $this->assertEquals('DE, FRA', $pop->getName());
        $this->assertInstanceOf('Didww\Item\VoiceInTrunkGroup', $voiceInTrunkGroup);

        $this->assertInstanceOf('Didww\Item\Configuration\Pstn', $voiceInTrunks[0]->getConfiguration());
        $this->assertInstanceOf('Didww\Item\Configuration\Sip', $voiceInTrunks[1]->getConfiguration());

        $this->assertEquals($voiceInTrunksDocument->getMeta()['total_records'], 68);
    }

    public function testCreatePstnTrunk()
    {
        $attributes = [
            'configuration' => new \Didww\Item\Configuration\PSTN([
                'dst' => '558540420024',
            ]),
            'name' => 'hello, test pstn trunk',
        ];
        $voiceInTrunk = new \Didww\Item\VoiceInTrunk($attributes);
        $voiceInTrunkDocument = $voiceInTrunk->save();
        $voiceInTrunk = $voiceInTrunkDocument->getData();
        $this->assertInstanceOf('Didww\Item\VoiceInTrunk', $voiceInTrunk);
        $this->assertInstanceOf('Didww\Item\Configuration\Pstn', $voiceInTrunk->getConfiguration());
        $this->assertEquals('41b94706-325e-4704-a433-d65105758836', $voiceInTrunk->getId());
        $this->assertEquals($attributes['configuration']->getAttributes(), $voiceInTrunk->getConfiguration()->getAttributes());
        $this->assertEquals($attributes['name'], $voiceInTrunk->getName());
    }

    public function testUpdatePstnTrunk()
    {
        $attributes = [
            'configuration' => new \Didww\Item\Configuration\PSTN([
                'dst' => '558540420025',
            ]),
            'name' => 'hello, updated test pstn trunk',
        ];
        $voiceInTrunk = \Didww\Item\VoiceInTrunk::build('41b94706-325e-4704-a433-d65105758836', $attributes);
        $voiceInTrunkDocument = $voiceInTrunk->save();
        $voiceInTrunk = $voiceInTrunkDocument->getData();
        $this->assertInstanceOf('Didww\Item\VoiceInTrunk', $voiceInTrunk);
        $this->assertInstanceOf('Didww\Item\Configuration\Pstn', $voiceInTrunk->getConfiguration());
        $this->assertEquals('558540420025', $voiceInTrunk->getConfiguration()->getDst());
        $this->assertEquals($attributes['name'], $voiceInTrunk->getName());
    }

    public function testCreateSipTrunk()
    {
        $attributes = [
            'configuration' => new \Didww\Item\Configuration\Sip([
                'username' => 'username',
                'host' => '216.58.215.110',
                'sst_refresh_method_id' => 1,
                'port' => 5060,
                'codec_ids' => \Didww\Item\Configuration\Base::getDefaultCodecIds(),
                'rerouting_disconnect_code_ids' => \Didww\Item\Configuration\Base::getDefaultReroutingDisconnectCodeIds(),
                'media_encryption_mode' => 'zrtp',
                'stir_shaken_mode' => 'pai',
                'allowed_rtp_ips' => ['127.0.0.1'],
            ]),
            'name' => 'hello, test sip trunk',
        ];
        $voiceInTrunk = new \Didww\Item\VoiceInTrunk($attributes);
        $voiceInTrunkDocument = $voiceInTrunk->save();
        $voiceInTrunk = $voiceInTrunkDocument->getData();
        $this->assertInstanceOf('Didww\Item\VoiceInTrunk', $voiceInTrunk);

        $sipConfiguration = $voiceInTrunk->getConfiguration();

        $this->assertInstanceOf('Didww\Item\Configuration\Sip', $sipConfiguration);
        $this->assertArraySubset($attributes['configuration']->getAttributes(), $sipConfiguration->getAttributes());

        $this->assertEquals('216.58.215.110', $sipConfiguration->getHost());
        $this->assertEquals(SstRefreshMethod::INVITE, $sipConfiguration->getSstRefreshMethodId());
        $this->assertEquals(5060, $sipConfiguration->getPort());
        $this->assertEquals(\Didww\Item\Configuration\Base::getDefaultCodecIds(), $sipConfiguration->getCodecIds());
        $this->assertEquals(\Didww\Item\Configuration\Base::getDefaultReroutingDisconnectCodeIds(), $sipConfiguration->getReroutingDisconnectCodeIds());
        $this->assertEquals('username', $sipConfiguration->getUsername());
        $this->assertEquals(MediaEncryptionMode::ZRTP, $sipConfiguration->getMediaEncryptionMode());
        $this->assertEquals(StirShakenMode::PAI, $sipConfiguration->getStirShakenMode());
        $this->assertEquals(['127.0.0.1'], $sipConfiguration->getAllowedRtpIps());
        $this->assertEquals($attributes['name'], $voiceInTrunk->getName());

        // Additional SIP getter assertions
        $this->assertFalse($sipConfiguration->getAuthEnabled());
        $this->assertNull($sipConfiguration->getAuthUser());
        $this->assertNull($sipConfiguration->getAuthPassword());
        $this->assertNull($sipConfiguration->getAuthFromUser());
        $this->assertNull($sipConfiguration->getAuthFromDomain());
        $this->assertFalse($sipConfiguration->getSstEnabled());
        $this->assertEquals(600, $sipConfiguration->getSstMinTimer());
        $this->assertEquals(900, $sipConfiguration->getSstMaxTimer());
        $this->assertTrue($sipConfiguration->getSstAccept501());
        $this->assertNull($sipConfiguration->getSstSessionExpires());
        $this->assertEquals(8000, $sipConfiguration->getSipTimerB());
        $this->assertEquals(2000, $sipConfiguration->getDnsFailoverTimer());
        $this->assertFalse($sipConfiguration->getRtpPing());
        $this->assertFalse($sipConfiguration->getForceSymmetricRtp());
        $this->assertFalse($sipConfiguration->getResolveRuri());
        $this->assertEquals(RxDtmfFormat::RFC_2833, $sipConfiguration->getRxDtmfFormatId());
        $this->assertEquals(TxDtmfFormat::RFC_2833, $sipConfiguration->getTxDtmfFormatId());
        $this->assertEquals(TransportProtocol::UDP, $sipConfiguration->getTransportProtocolId());
        $this->assertEquals(0, $sipConfiguration->getMaxTransfers());
        $this->assertEquals(0, $sipConfiguration->getMax30xRedirects());

        // VoiceInTrunk-level getter assertions
        $this->assertEquals(1, $voiceInTrunk->getPriority());
        $this->assertEquals(65535, $voiceInTrunk->getWeight());
        $this->assertInstanceOf(\DateTime::class, $voiceInTrunk->getCreatedAt());
        $this->assertEquals(CliFormat::E164, $voiceInTrunk->getCliFormat());
    }

    public function testUpdateSipTrunk()
    {
        $attributes = [
            'configuration' => new \Didww\Item\Configuration\Sip([
                'username' => 'new-username',
                'max_transfers' => 5,
            ]),
            'name' => 'hello, updated test sip trunk',
            'description' => 'just a description',
        ];
        $voiceInTrunk = \Didww\Item\VoiceInTrunk::build('a80006b6-4183-4865-8b99-7ebbd359a762', $attributes);
        $voiceInTrunkDocument = $voiceInTrunk->save();
        $voiceInTrunk = $voiceInTrunkDocument->getData();
        $this->assertInstanceOf('Didww\Item\VoiceInTrunk', $voiceInTrunk);
        $this->assertInstanceOf('Didww\Item\Configuration\Sip', $voiceInTrunk->getConfiguration());
        $this->assertArraySubset($attributes['configuration']->getAttributes(), $voiceInTrunk->getConfiguration()->getAttributes());
        $this->assertEquals($attributes['name'], $voiceInTrunk->getName());
        $this->assertEquals($attributes['description'], $voiceInTrunk->getDescription());
    }

    public function testDeleteTrunk()
    {
        $voiceInTrunk = \Didww\Item\VoiceInTrunk::build('41b94706-325e-4704-a433-d65105758836');

        $voiceInTrunkDocument = $voiceInTrunk->delete();

        $this->assertFalse($voiceInTrunkDocument->hasErrors());
    }

    public function testUpdateRelationships()
    {
        $voiceInTrunk = \Didww\Item\VoiceInTrunk::build('a80006b6-4183-4865-8b99-7ebbd359a762');
        $voiceInTrunk->setPop(\Didww\Item\Pop::build('ba7ccbef-82ac-4372-9391-eac90d5c9479'));
        $voiceInTrunk->setVoiceInTrunkGroup(\Didww\Item\VoiceInTrunkGroup::build('837c5764-a6c3-456f-aa37-71fc8f8ca07b'));
        $voiceInTrunkDocument = $voiceInTrunk->save(['include' => 'pop,trunk_group']);
        $this->assertFalse($voiceInTrunkDocument->hasErrors());
        $voiceInTrunk = $voiceInTrunkDocument->getData();

        $voiceInTrunkGroup = $voiceInTrunk->voiceInTrunkGroup()->getIncluded();
        $pop = $voiceInTrunk->pop()->getIncluded();

        $this->assertInstanceOf('Didww\Item\Pop', $pop);
        $this->assertInstanceOf('Didww\Item\VoiceInTrunkGroup', $voiceInTrunkGroup);
        $this->assertEquals('ba7ccbef-82ac-4372-9391-eac90d5c9479', $pop->getId());
        $this->assertEquals('837c5764-a6c3-456f-aa37-71fc8f8ca07b', $voiceInTrunkGroup->getId());
    }

    public function testSipSetters()
    {
        $sip = new \Didww\Item\Configuration\Sip();

        $sip->setHost('10.0.0.1');
        $this->assertEquals('10.0.0.1', $sip->getHost());

        $sip->setUsername('testuser');
        $this->assertEquals('testuser', $sip->getUsername());

        $sip->setPort(5061);
        $this->assertEquals(5061, $sip->getPort());

        $sip->setCodecIds([9, 10]);
        $this->assertEquals([\Didww\Enum\Codec::PCMU, \Didww\Enum\Codec::PCMA], $sip->getCodecIds());

        $sip->setRxDtmfFormatId(1);
        $this->assertEquals(RxDtmfFormat::RFC_2833, $sip->getRxDtmfFormatId());

        $sip->setTxDtmfFormatId(1);
        $this->assertEquals(TxDtmfFormat::RFC_2833, $sip->getTxDtmfFormatId());

        $sip->setResolveRuri(true);
        $this->assertTrue($sip->getResolveRuri());

        $sip->setAuthEnabled(true);
        $this->assertTrue($sip->getAuthEnabled());

        $sip->setAuthUser('user');
        $this->assertEquals('user', $sip->getAuthUser());

        $sip->setAuthPassword('pass');
        $this->assertEquals('pass', $sip->getAuthPassword());

        $sip->setAuthFromUser('from_user');
        $this->assertEquals('from_user', $sip->getAuthFromUser());

        $sip->setAuthFromDomain('example.com');
        $this->assertEquals('example.com', $sip->getAuthFromDomain());

        $sip->setSstEnabled(true);
        $this->assertTrue($sip->getSstEnabled());

        $sip->setSstRefreshMethodId(1);
        $this->assertEquals(SstRefreshMethod::INVITE, $sip->getSstRefreshMethodId());

        $sip->setSstMinTimer(300);
        $this->assertEquals(300, $sip->getSstMinTimer());

        $sip->setSstMaxTimer(1800);
        $this->assertEquals(1800, $sip->getSstMaxTimer());

        $sip->setSstAccept501(false);
        $this->assertFalse($sip->getSstAccept501());

        $sip->setSstSessionExpires(1800);
        $this->assertEquals(1800, $sip->getSstSessionExpires());

        $sip->setSipTimerB(16000);
        $this->assertEquals(16000, $sip->getSipTimerB());

        $sip->setDnsFailoverTimer(4000);
        $this->assertEquals(4000, $sip->getDnsFailoverTimer());

        $sip->setRtpPing(true);
        $this->assertTrue($sip->getRtpPing());

        $sip->setForceSymmetricRtp(true);
        $this->assertTrue($sip->getForceSymmetricRtp());

        $sip->setReroutingDisconnectCodeIds([56, 58]);
        $this->assertEquals(
            [\Didww\Enum\ReroutingDisconnectCode::SIP_400_BAD_REQUEST, \Didww\Enum\ReroutingDisconnectCode::SIP_402_PAYMENT_REQUIRED],
            $sip->getReroutingDisconnectCodeIds()
        );

        $sip->setTransportProtocolId(1);
        $this->assertEquals(TransportProtocol::UDP, $sip->getTransportProtocolId());

        $sip->setMaxTransfers(3);
        $this->assertEquals(3, $sip->getMaxTransfers());

        $sip->setMax30xRedirects(2);
        $this->assertEquals(2, $sip->getMax30xRedirects());

        $sip->setMediaEncryptionMode('zrtp');
        $this->assertEquals(MediaEncryptionMode::ZRTP, $sip->getMediaEncryptionMode());

        $sip->setStirShakenMode('pai');
        $this->assertEquals(StirShakenMode::PAI, $sip->getStirShakenMode());

        $sip->setAllowedRtpIps(['192.168.1.1']);
        $this->assertEquals(['192.168.1.1'], $sip->getAllowedRtpIps());
    }

    public function testVoiceInTrunkSetters()
    {
        $trunk = new \Didww\Item\VoiceInTrunk();

        $trunk->setName('test trunk');
        $this->assertEquals('test trunk', $trunk->getName());

        $trunk->setPriority(2);
        $this->assertEquals(2, $trunk->getPriority());

        $trunk->setWeight(100);
        $this->assertEquals(100, $trunk->getWeight());

        $trunk->setCliFormat('raw');
        $this->assertEquals(CliFormat::RAW, $trunk->getCliFormat());

        $trunk->setCliPrefix('+1');
        $this->assertEquals('+1', $trunk->getCliPrefix());

        $trunk->setDescription('test description');
        $this->assertEquals('test description', $trunk->getDescription());

        $trunk->setRingingTimeout(30);
        $this->assertEquals(30, $trunk->getRingingTimeout());

        $config = new \Didww\Item\Configuration\Sip(['host' => '1.2.3.4']);
        $trunk->setConfiguration($config);
        $this->assertSame($config, $trunk->getConfiguration());
    }
}
