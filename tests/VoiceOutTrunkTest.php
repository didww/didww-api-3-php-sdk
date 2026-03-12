<?php

namespace Didww\Tests;

use Didww\Enum\DefaultDstAction;
use Didww\Enum\MediaEncryptionMode;
use Didww\Enum\OnCliMismatchAction;
use Didww\Enum\VoiceOutTrunkStatus;

class VoiceOutTrunkTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'voice_out_trunks.yml';
    }

    public function testCreateVoiceOutTrunk()
    {
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

        // Typed getter assertions
        $this->assertEquals('php-test', $data->getName());
        $this->assertEquals(['0.0.0.0/0'], $data->getAllowedSipIps());
        $this->assertEquals(OnCliMismatchAction::REPLACE_CLI, $data->getOnCliMismatchAction());
        $this->assertNull($data->getAllowedRtpIps());
        $this->assertFalse($data->getAllowAnyDidAsCli());
        $this->assertEquals(VoiceOutTrunkStatus::ACTIVE, $data->getStatus());
        $this->assertEquals(MediaEncryptionMode::DISABLED, $data->getMediaEncryptionMode());
        $this->assertEquals(DefaultDstAction::ALLOW_ALL, $data->getDefaultDstAction());
        $this->assertEquals([], $data->getDstPrefixes());
        $this->assertFalse($data->getForceSymmetricRtp());
        $this->assertFalse($data->getRtpPing());
        $this->assertNull($data->getCallbackUrl());
        $this->assertNull($data->getThresholdAmount());
        $this->assertInstanceOf(\DateTime::class, $data->getCreatedAt());
    }

    public function testAllVoiceOutTrunks()
    {
        $voiceOutTrunksDocument = \Didww\Item\VoiceOutTrunk::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\VoiceOutTrunk', $voiceOutTrunksDocument->getData());
    }

    public function testFindVoiceOutTrunk()
    {
        $uuid = '425ce763-a3a9-49b4-af5b-ada1a65c8864';
        $voiceOutTrunkDocument = \Didww\Item\VoiceOutTrunk::find($uuid, ['include' => 'dids,default_did']);

        $data = $voiceOutTrunkDocument->getData();
        $this->assertInstanceOf('Didww\Item\VoiceOutTrunk', $data);

        $didsRelation = $data->dids();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Did', $didsRelation->getIncluded()->all());

        $defaultDidRelation = $data->defaultDid();
        $this->assertInstanceOf('Didww\Item\Did', $defaultDidRelation->getIncluded());
        $this->assertEquals('7de7f718-4042-4d74-9fe9-863fa1777520', $defaultDidRelation->getIncluded()->getId());
    }

    public function testUpdateVoiceOutTrunk()
    {
        $uuid = '425ce763-a3a9-49b4-af5b-ada1a65c8864';
        $voiceOutTrunk = \Didww\Item\VoiceOutTrunk::build($uuid);
        $voiceOutTrunk->setMediaEncryptionMode('disabled');
        $voiceOutTrunkDocument = $voiceOutTrunk->save();

        $data = $voiceOutTrunkDocument->getData();
        $this->assertInstanceOf('Didww\Item\VoiceOutTrunk', $data);
        $this->assertEquals(MediaEncryptionMode::DISABLED, $data->getMediaEncryptionMode());
    }

    public function testDeleteVoiceOutTrunk()
    {
        $uuid = '425ce763-a3a9-49b4-af5b-ada1a65c8864';
        $voiceOutTrunk = \Didww\Item\VoiceOutTrunk::build($uuid);
        $voiceOutTrunkDocument = $voiceOutTrunk->delete();
        $this->assertFalse($voiceOutTrunkDocument->hasErrors());
    }

    public function testVoiceOutTrunkSetters()
    {
        $trunk = new \Didww\Item\VoiceOutTrunk();

        $trunk->setName('my-trunk');
        $this->assertEquals('my-trunk', $trunk->getName());

        $trunk->setAllowedSipIps(['10.0.0.1/32']);
        $this->assertEquals(['10.0.0.1/32'], $trunk->getAllowedSipIps());

        $trunk->setOnCliMismatchAction('reject_call');
        $this->assertEquals(OnCliMismatchAction::REJECT_CALL, $trunk->getOnCliMismatchAction());

        $trunk->setAllowedRtpIps(['192.168.0.1']);
        $this->assertEquals(['192.168.0.1'], $trunk->getAllowedRtpIps());

        $trunk->setAllowAnyDidAsCli(true);
        $this->assertTrue($trunk->getAllowAnyDidAsCli());

        $trunk->setStatus('active');
        $this->assertEquals(VoiceOutTrunkStatus::ACTIVE, $trunk->getStatus());

        $trunk->setMediaEncryptionMode('disabled');
        $this->assertEquals(MediaEncryptionMode::DISABLED, $trunk->getMediaEncryptionMode());

        $trunk->setDefaultDstAction('reject_all');
        $this->assertEquals(DefaultDstAction::REJECT_ALL, $trunk->getDefaultDstAction());

        $trunk->setDstPrefixes(['1', '44']);
        $this->assertEquals(['1', '44'], $trunk->getDstPrefixes());

        $trunk->setForceSymmetricRtp(true);
        $this->assertTrue($trunk->getForceSymmetricRtp());

        $trunk->setRtpPing(true);
        $this->assertTrue($trunk->getRtpPing());

        $trunk->setCallbackUrl('https://example.com/cb');
        $this->assertEquals('https://example.com/cb', $trunk->getCallbackUrl());

        $trunk->setThresholdAmount('100.00');
        $this->assertEquals('100.00', $trunk->getThresholdAmount());

        $trunk->setCapacityLimit('50');
        $this->assertEquals('50', $trunk->getCapacityLimit());
    }
}
