<?php

namespace Didww\Tests;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

class VoiceInTrunkTest extends BaseTest
{
    use ArraySubsetAsserts;

    public function testAllWithIncludesAndPagination()
    {
        $this->startVCR('voice_in_trunks.yml');

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

        $this->stopVCR();
    }

    public function testCreatePstnTrunk()
    {
        $this->startVCR('voice_in_trunks.yml');
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
        $this->stopVCR();
    }

    public function testUpdatePstnTrunk()
    {
        $this->startVCR('voice_in_trunks.yml');
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
        $this->stopVCR();
    }

    public function testCreateSipTrunk()
    {
        $this->startVCR('voice_in_trunks.yml');
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
        $this->assertEquals(1, $sipConfiguration->getSstRefreshMethodId());
        $this->assertEquals(5060, $sipConfiguration->getPort());
        $this->assertEquals(\Didww\Item\Configuration\Base::getDefaultCodecIds(), $sipConfiguration->getCodecIds());
        $this->assertEquals(\Didww\Item\Configuration\Base::getDefaultReroutingDisconnectCodeIds(), $sipConfiguration->getReroutingDisconnectCodeIds());
        $this->assertEquals('username', $sipConfiguration->getUsername());
        $this->assertEquals('zrtp', $sipConfiguration->getMediaEncryptionMode());
        $this->assertEquals('pai', $sipConfiguration->getStirShakenMode());
        $this->assertEquals(['127.0.0.1'], $sipConfiguration->getAllowedRtpIps());
        $this->assertEquals($attributes['name'], $voiceInTrunk->getName());
        $this->stopVCR();
    }

    public function testUpdateSipTrunk()
    {
        $this->startVCR('voice_in_trunks.yml');
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
        $this->stopVCR();
    }

    public function testDeleteTrunk()
    {
        $this->startVCR('voice_in_trunks.yml');

        $voiceInTrunk = \Didww\Item\VoiceInTrunk::build('41b94706-325e-4704-a433-d65105758836');

        $voiceInTrunkDocument = $voiceInTrunk->delete();

        $this->assertFalse($voiceInTrunkDocument->hasErrors());
        $this->stopVCR();
    }

    public function testUpdateRelashionships()
    {
        $this->startVCR('voice_in_trunks.yml');
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

        $this->stopVCR();
    }
}
