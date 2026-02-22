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
        $voiceInTrunksArray = is_array($voiceInTrunks) ? $voiceInTrunks : iterator_to_array($voiceInTrunks);
        $pstnTrunk = array_values(array_filter($voiceInTrunksArray, fn($t) => $t->getConfiguration() instanceof \Didww\Item\Configuration\Pstn))[0] ?? null;
        $sipTrunk = array_values(array_filter($voiceInTrunksArray, fn($t) => $t->getConfiguration() instanceof \Didww\Item\Configuration\Sip))[0] ?? null;

        $this->assertNotNull($pstnTrunk);
        $this->assertNotNull($sipTrunk);

        $pop = $sipTrunk->pop()->getIncluded();
        $this->assertInstanceOf('Didww\Item\Pop', $pop);
        $this->assertEquals('DE, FRA', $pop->getName());

        $this->assertEquals($voiceInTrunksDocument->getMeta()['total_records'], 70);

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

}
