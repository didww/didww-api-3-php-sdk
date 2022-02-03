<?php

namespace Didww\Tests;

class VoiceOutTrunkTest extends BaseTest
{
    public function testCreateVoiceOutTrunk()
    {
        $this->startVCR('voice_out_trunks.yml');

        $did = \Didww\Item\Did::build('7a028c32-e6b6-4c86-bf01-90f901b37012');
        $dids = new \Swis\JsonApi\Client\Collection([$did]);
        $voiceOutTrunk = new \Didww\Item\VoiceOutTrunk();
        $voiceOutTrunk->setName('php-test');
        $voiceOutTrunk->setAllowedSipIps(['0.0.0.0/0']);
        $voiceOutTrunk->setOnCliMismatchAction('replace_cli');
        $voiceOutTrunk->setDefaultDid($did);
        $voiceOutTrunk->setDids($dids);
        $voiceOutTrunkDocument = $voiceOutTrunk->save();

        $data = $voiceOutTrunkDocument->getData();
        $this->assertInstanceOf('Didww\Item\VoiceOutTrunk', $data);
        $this->assertEquals(
            'b60201c1-21f0-4d9a-aafa-0e6d1e12f22e',
            $data->getId()
        );
        $this->assertEquals(
            [
                'name' => 'php-test',
                'allowed_sip_ips' => ['0.0.0.0/0'],
                'on_cli_mismatch_action' => 'replace_cli',
                'created_at' => '2022-02-03T08:21:29.798Z',
                'allowed_rtp_ips' => null,
                'allow_any_did_as_cli' => false,
                'status' => 'active',
                'capacity_limit' => null,
                'username' => 'qkut5v4xwm',
                'password' => 'np34mftrrq',
                'threshold_reached' => false,
                'threshold_amount' => null,
                'media_encryption_mode' => 'disabled',
                'default_dst_action' => 'allow_all',
                'dst_prefixes' => [],
                'force_symmetric_rtp' => false,
                'rtp_ping' => false,
                'callback_url' => null,
            ],
            $data->getAttributes()
        );

        $this->stopVCR();
    }

    public function testAllVoiceOutTrunks()
    {
        $this->startVCR('voice_out_trunks.yml');

        $voiceOutTrunksDocument = \Didww\Item\VoiceOutTrunk::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\VoiceOutTrunk', $voiceOutTrunksDocument->getData());

        $this->stopVCR();
    }

    public function testFindVoiceOutTrunk()
    {
        $this->startVCR('voice_out_trunks.yml');

        $uuid = '425ce763-a3a9-49b4-af5b-ada1a65c8864';
        $voiceOutTrunkDocument = \Didww\Item\VoiceOutTrunk::find($uuid, ['include' => 'dids,default_did']);

        $data = $voiceOutTrunkDocument->getData();
        $this->assertInstanceOf('Didww\Item\VoiceOutTrunk', $data);

        $didsRelation = $data->dids();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Did', $didsRelation->getIncluded()->all());

        $defaultDidRelation = $data->defaultDid();
        $this->assertInstanceOf('Didww\Item\Did', $defaultDidRelation->getIncluded());
        $this->assertEquals('7de7f718-4042-4d74-9fe9-863fa1777520', $defaultDidRelation->getIncluded()->getId());

        $this->stopVCR();
    }

    public function testUpdateVoiceOutTrunk()
    {
        $this->startVCR('voice_out_trunks.yml');

        $uuid = '425ce763-a3a9-49b4-af5b-ada1a65c8864';
        $voiceOutTrunk = \Didww\Item\VoiceOutTrunk::build($uuid);
        $voiceOutTrunk->setMediaEncryptionMode('disabled');
        $voiceOutTrunkDocument = $voiceOutTrunk->save();

        $data = $voiceOutTrunkDocument->getData();
        $this->assertInstanceOf('Didww\Item\VoiceOutTrunk', $data);
        $this->assertEquals('disabled', $data->getMediaEncryptionMode());

        $this->stopVCR();
    }

    public function testDeleteVoiceOutTrunk()
    {
        $this->startVCR('voice_out_trunks.yml');

        $uuid = '425ce763-a3a9-49b4-af5b-ada1a65c8864';
        $voiceOutTrunk = \Didww\Item\VoiceOutTrunk::build($uuid);
        $voiceOutTrunkDocument = $voiceOutTrunk->delete();
        $this->assertFalse($voiceOutTrunkDocument->hasErrors());

        $this->stopVCR();
    }
}
