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

        $authMethod = new \Didww\Item\AuthenticationMethod\IpOnly(['allowed_sip_ips' => ['203.0.113.0/24']]);

        $voiceOutTrunk = new \Didww\Item\VoiceOutTrunk();
        $voiceOutTrunk->setName('php-test');
        $voiceOutTrunk->setAuthenticationMethod($authMethod);
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

        // Verify authentication_method is parsed correctly
        $am = $data->getAuthenticationMethod();
        $this->assertInstanceOf(\Didww\Item\AuthenticationMethod\CredentialsAndIp::class, $am);
        $this->assertEquals(['203.0.113.0/24'], $am->getAllowedSipIps());
        $this->assertEquals('qkut5v4xwm', $am->getUsername());
        $this->assertEquals('np34mftrrq', $am->getPassword());

        // Typed getter assertions
        $this->assertEquals('php-test', $data->getName());
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

    public function testAuthenticationMethodPolymorphicParsing()
    {
        // IpOnly
        $ipOnly = \Didww\Item\AuthenticationMethod\Base::fromArray([
            'type' => 'ip_only',
            'attributes' => ['allowed_sip_ips' => ['10.0.0.1/32'], 'tech_prefix' => 'abc'],
        ]);
        $this->assertInstanceOf(\Didww\Item\AuthenticationMethod\IpOnly::class, $ipOnly);
        $this->assertEquals(['10.0.0.1/32'], $ipOnly->getAllowedSipIps());
        $this->assertEquals('abc', $ipOnly->getTechPrefix());

        // CredentialsAndIp
        $credIp = \Didww\Item\AuthenticationMethod\Base::fromArray([
            'type' => 'credentials_and_ip',
            'attributes' => ['allowed_sip_ips' => ['10.0.0.1/32'], 'username' => 'user1', 'password' => 'pass1'],
        ]);
        $this->assertInstanceOf(\Didww\Item\AuthenticationMethod\CredentialsAndIp::class, $credIp);
        $this->assertEquals('user1', $credIp->getUsername());
        $this->assertEquals('pass1', $credIp->getPassword());

        // Twilio
        $twilio = \Didww\Item\AuthenticationMethod\Base::fromArray([
            'type' => 'twilio',
            'attributes' => ['twilio_account_sid' => 'AC123'],
        ]);
        $this->assertInstanceOf(\Didww\Item\AuthenticationMethod\Twilio::class, $twilio);
        $this->assertEquals('AC123', $twilio->getTwilioAccountSid());

        // Unknown type -> Generic
        $generic = \Didww\Item\AuthenticationMethod\Base::fromArray([
            'type' => 'future_type',
            'attributes' => ['foo' => 'bar'],
        ]);
        $this->assertInstanceOf(\Didww\Item\AuthenticationMethod\Generic::class, $generic);
        $this->assertEquals('future_type', $generic->getType());
    }

    public function testVoiceOutTrunkExternalReferenceId()
    {
        $trunk = new \Didww\Item\VoiceOutTrunk();

        $trunk->setExternalReferenceId('ref-789');
        $this->assertEquals('ref-789', $trunk->getExternalReferenceId());

        $trunk->setExternalReferenceId(null);
        $this->assertNull($trunk->getExternalReferenceId());
    }

    public function testVoiceOutTrunkEmergencyDids()
    {
        $trunk = new \Didww\Item\VoiceOutTrunk();

        $emergencyDids = new \Swis\JsonApi\Client\Collection([
            \Didww\Item\Did::build('did-1'),
            \Didww\Item\Did::build('did-2'),
        ]);
        $trunk->setEmergencyDids($emergencyDids);

        $relation = $trunk->emergencyDids();
        $this->assertNotNull($relation);
    }

    public function testVoiceOutTrunkRtpTimeout()
    {
        $trunk = new \Didww\Item\VoiceOutTrunk();

        $trunk->setRtpTimeout(30);
        $this->assertEquals(30, $trunk->getRtpTimeout());

        $trunk->setRtpTimeout(null);
        $this->assertNull($trunk->getRtpTimeout());
    }

    public function testVoiceOutTrunkEmergencyEnableAll()
    {
        $trunk = new \Didww\Item\VoiceOutTrunk();

        $trunk->setEmergencyEnableAll(true);
        $this->assertTrue($trunk->getEmergencyEnableAll());

        $trunk->setEmergencyEnableAll(false);
        $this->assertFalse($trunk->getEmergencyEnableAll());
    }

    public function testAuthenticationMethodSerialization()
    {
        $ipOnly = new \Didww\Item\AuthenticationMethod\IpOnly([
            'allowed_sip_ips' => ['203.0.113.0/24'],
            'tech_prefix' => 'test',
        ]);
        $this->assertEquals([
            'type' => 'ip_only',
            'attributes' => [
                'allowed_sip_ips' => ['203.0.113.0/24'],
                'tech_prefix' => 'test',
            ],
        ], $ipOnly->toJsonApiArray());
    }
}
