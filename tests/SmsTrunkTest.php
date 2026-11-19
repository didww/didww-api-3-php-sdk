<?php

namespace Didww\Tests;

use Didww\Enum\SenderIdVerificationStatus;
use Didww\Enum\SenderType;
use Didww\Enum\SmsBodyType;
use Didww\Enum\SmsHttpMethod;
use Didww\Enum\SmsServerLocation;
use Didww\Item\SmsTrunk;
use Didww\Item\SmsTrunkConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;

class SmsTrunkTest extends CassetteTest
{
    private const HTTP_IN_ID = '1a2b3c4d-0002-4a5b-8c6d-7e8f9a0b1c02';
    private const HTTP_OUT_ID = '1a2b3c4d-0003-4a5b-8c6d-7e8f9a0b1c03';
    private const UNKNOWN_ID = '1a2b3c4d-0006-4a5b-8c6d-7e8f9a0b1c06';
    private const GROUP_ID = '2b3c4d5e-0001-4b6c-9d7e-8f9a0b1c2d01';

    protected function getCassetteName(): string
    {
        return 'sms_trunks.yml';
    }

    public function testAll()
    {
        $trunks = SmsTrunk::all()->getData();
        $this->assertCount(5, $trunks);
        $this->assertContainsOnlyInstancesOf(SmsTrunk::class, $trunks);
        $this->assertSame(
            ['sms_to_email', 'http_in', 'http_out', 'smpp_esme', 'smpp_smsc'],
            array_map(fn (SmsTrunk $t) => $t->getConfiguration()->getType(), $trunks->all())
        );

        $email = $trunks[0];
        $this->assertSame('1a2b3c4d-0001-4a5b-8c6d-7e8f9a0b1c01', $email->getId());
        $this->assertSame('sms_to_email trunk', $email->getName());
        $this->assertNull($email->getExternalReferenceId());
        $this->assertFalse($email->getBlocked());
        $this->assertSame(100, $email->getPriority());
        $this->assertFalse($email->getAllowAnyDidAsSource());
        $this->assertTrue($email->getAllowIn());
        $this->assertFalse($email->getAllowOut());
        $this->assertEquals(new \DateTime('2026-10-01T10:00:00Z'), $email->getCreatedAt());
        $this->assertEquals(new \DateTime('2026-10-01T10:00:00Z'), $email->getUpdatedAt());
        $this->assertNull($email->getHost());
        $emailCfg = $email->getConfiguration();
        $this->assertInstanceOf(SmsTrunkConfiguration\SmsToEmail::class, $emailCfg);
        $this->assertSame('sms@example.com', $emailCfg->getSendTo());
        $this->assertSame('Inbound SMS', $emailCfg->getSubject());
        $this->assertSame('You have a new message.', $emailCfg->getMessage());

        $httpIn = $trunks[1]->getConfiguration();
        $this->assertInstanceOf(SmsTrunkConfiguration\HttpIn::class, $httpIn);
        $this->assertSame('https://example.com/sms', $httpIn->getUrl());
        $this->assertSame(SmsHttpMethod::POST, $httpIn->getHttpMethod());
        $this->assertNull($httpIn->getQueryParameters());
        $this->assertSame(['X-Source' => 'didww'], $httpIn->getHeaders());
        $this->assertSame(SmsBodyType::JSON, $httpIn->getBodyType());
        $this->assertSame('{"text":"{SMS_TEXT}","from":"{SMS_SRC_ADDR}"}', $httpIn->getBody());
        $this->assertFalse($httpIn->getFollowRedirect());
        $this->assertTrue($httpIn->getValidateHttpsCertificate());

        $out = $trunks[2];
        $this->assertNull($out->getPriority());
        $this->assertTrue($out->getAllowOut());
        $this->assertSame('https://sms-out.example.com/outbound_messages', $out->getHost());
        $httpOut = $out->getConfiguration();
        $this->assertInstanceOf(SmsTrunkConfiguration\HttpOut::class, $httpOut);
        $this->assertSame('https://example.com/sms/status', $httpOut->getCallbackUrl());
        $this->assertSame(['203.0.113.10'], $httpOut->getAllowedIps());
        $this->assertSame('generated-name', $httpOut->getAuthName());
        $this->assertSame('generated-password', $httpOut->getAuthPassword());

        $esme = $trunks[3]->getConfiguration();
        $this->assertInstanceOf(SmsTrunkConfiguration\SmppEsme::class, $esme);
        $this->assertNull($esme->getSystemType());
        $this->assertSame(['203.0.113.10'], $esme->getAllowedIps());
        $this->assertSame(SmsServerLocation::NYC, $esme->getServerLocation());
        $this->assertSame('esme1234', $esme->getSystemId());
        $this->assertSame('gen12345', $esme->getPassword());

        $smsc = $trunks[4]->getConfiguration();
        $this->assertInstanceOf(SmsTrunkConfiguration\SmppSmsc::class, $smsc);
        $this->assertSame('smsc.example.com', $smsc->getHost());
        $this->assertSame(2775, $smsc->getTxPort());
        $this->assertSame(2776, $smsc->getRxPort());
        $this->assertTrue($smsc->getTransceiverMode());
        $this->assertSame(30, $smsc->getConnectionTimeout());
        $this->assertFalse($smsc->getUseSsl());
        $this->assertSame('didww', $smsc->getSystemId());
        $this->assertNull($smsc->getSystemType());
        $this->assertSame('secret', $smsc->getPassword());
        $this->assertSame(SmsServerLocation::NYC, $smsc->getServerLocation());
    }

    public function testAllWithIncludedSmsTrunkGroup()
    {
        $document = SmsTrunk::all([
            'filter' => ['configuration.type' => 'http_in'],
            'include' => 'sms_trunk_group',
            'sort' => '-priority',
        ]);
        $trunks = $document->getData();
        $this->assertCount(1, $trunks);
        $group = $trunks[0]->smsTrunkGroup()->getIncluded();
        $this->assertInstanceOf(\Didww\Item\SmsTrunkGroup::class, $group);
        $this->assertSame(self::GROUP_ID, $group->getId());
        $this->assertSame('Inbound SMS', $group->getName());
        $this->assertSame(1, $group->getSmsTrunksCount());
    }

    public function testFind()
    {
        $trunk = SmsTrunk::find(self::HTTP_OUT_ID)->getData();
        $this->assertInstanceOf(SmsTrunk::class, $trunk);
        $this->assertSame(self::HTTP_OUT_ID, $trunk->getId());
        $this->assertSame('http_out trunk', $trunk->getName());
        $this->assertSame('https://sms-out.example.com/outbound_messages', $trunk->getHost());
        $this->assertSame('generated-password', $trunk->getConfiguration()->getAuthPassword());
    }

    public function testFindWithIncludedSenderIdVerifications()
    {
        $trunk = SmsTrunk::find(self::HTTP_OUT_ID, ['include' => 'sender_id_verifications'])->getData();
        $verifications = $trunk->senderIdVerifications()->getIncluded();
        $this->assertCount(1, $verifications);
        $verification = $verifications[0];
        $this->assertInstanceOf(\Didww\Item\SenderIdVerification::class, $verification);
        $this->assertSame('e5a4e2f1-3b26-4a5c-9a0a-2f7c1f3e8d10', $verification->getId());
        $this->assertSame('ACME alerts', $verification->getName());
        $this->assertSame(SenderIdVerificationStatus::ACTIVE, $verification->getStatus());
        $this->assertSame(SenderType::LONG_CODE, $verification->getSenderType());
    }

    public function testFindNotFound()
    {
        $document = SmsTrunk::find('1a2b3c4d-0009-4a5b-8c6d-7e8f9a0b1c09');
        $this->assertTrue($document->hasErrors());
        $this->assertSame(404, $document->getResponse()->getStatusCode());
        $this->assertSame('Record not found', $document->getErrors()->first()->getTitle());
    }

    #[DataProvider('createProvider')]
    public function testCreate(string $type, SmsTrunkConfiguration\Base $configuration, string $expectedClass)
    {
        $trunk = new SmsTrunk();
        $trunk->setName($type.' trunk');
        $trunk->setConfiguration($configuration);
        $document = $trunk->save();

        $this->assertFalse($document->hasErrors());
        $created = $document->getData();
        $this->assertNotEmpty($created->getId());
        $this->assertSame($type.' trunk', $created->getName());
        $this->assertInstanceOf($expectedClass, $created->getConfiguration());
        $this->assertSame($type, $created->getConfiguration()->getType());
    }

    public static function createProvider(): array
    {
        $email = new SmsTrunkConfiguration\SmsToEmail();
        $email->setSendTo('sms@example.com');
        $email->setSubject('Inbound SMS');
        $email->setMessage('You have a new message.');

        $httpIn = new SmsTrunkConfiguration\HttpIn();
        $httpIn->setUrl('https://example.com/sms');
        $httpIn->setHttpMethod(SmsHttpMethod::POST);
        $httpIn->setHeaders(['X-Source' => 'didww']);
        $httpIn->setBodyType(SmsBodyType::JSON);
        $httpIn->setBody('{"text":"{SMS_TEXT}","from":"{SMS_SRC_ADDR}"}');
        $httpIn->setFollowRedirect(false);
        $httpIn->setValidateHttpsCertificate(true);

        $httpOut = new SmsTrunkConfiguration\HttpOut([
            // Read-only credentials are never sent.
            'auth_name' => 'ignored-name',
            'auth_password' => 'ignored-password',
        ]);
        $httpOut->setCallbackUrl('https://example.com/sms/status');
        $httpOut->setAllowedIps(['203.0.113.10']);

        $esme = new SmsTrunkConfiguration\SmppEsme([
            'system_id' => 'ignored-id',
            'password' => 'ignored-password',
        ]);
        $esme->setAllowedIps(['203.0.113.10']);
        $esme->setServerLocation(SmsServerLocation::NYC);

        $smsc = new SmsTrunkConfiguration\SmppSmsc();
        $smsc->setHost('smsc.example.com');
        $smsc->setTxPort(2775);
        $smsc->setRxPort(2776);
        $smsc->setTransceiverMode(true);
        $smsc->setConnectionTimeout(30);
        $smsc->setUseSsl(false);
        $smsc->setSystemId('didww');
        $smsc->setPassword('secret');
        $smsc->setServerLocation('nyc');

        return [
            'sms_to_email' => ['sms_to_email', $email, SmsTrunkConfiguration\SmsToEmail::class],
            'http_in' => ['http_in', $httpIn, SmsTrunkConfiguration\HttpIn::class],
            'http_out' => ['http_out', $httpOut, SmsTrunkConfiguration\HttpOut::class],
            'smpp_esme' => ['smpp_esme', $esme, SmsTrunkConfiguration\SmppEsme::class],
            'smpp_smsc' => ['smpp_smsc', $smsc, SmsTrunkConfiguration\SmppSmsc::class],
        ];
    }

    public function testCreateWithGroupAndOutgoingDids()
    {
        $trunk = new SmsTrunk();
        $trunk->setName('smpp_smsc trunk');
        $trunk->setConfiguration(new SmsTrunkConfiguration\SmppSmsc(['host' => 'smsc.example.com', 'system_id' => 'didww', 'password' => 'secret', 'server_location' => 'nyc']));
        $trunk->setSmsTrunkGroup(\Didww\Item\SmsTrunkGroup::build(self::GROUP_ID));
        $trunk->setOutgoingDids(new \Swis\JsonApi\Client\Collection([\Didww\Item\Did::build('9df99644-f1a5-4a3c-99a4-559d758eb96b')]));

        $this->assertSame([
            'type' => 'sms_trunks',
            'attributes' => [
                'name' => 'smpp_smsc trunk',
                'configuration' => ['type' => 'smpp_smsc', 'attributes' => ['host' => 'smsc.example.com', 'system_id' => 'didww', 'password' => 'secret', 'server_location' => 'nyc']],
            ],
            'relationships' => [
                'sms_trunk_group' => ['data' => ['type' => 'sms_trunk_groups', 'id' => self::GROUP_ID]],
                'outgoing_dids' => ['data' => [['type' => 'dids', 'id' => '9df99644-f1a5-4a3c-99a4-559d758eb96b']]],
            ],
        ], $trunk->toJsonApiArray());

        $document = $trunk->save();
        $this->assertFalse($document->hasErrors());
        $this->assertInstanceOf(SmsTrunkConfiguration\SmppSmsc::class, $document->getData()->getConfiguration());
    }

    public function testCreateError()
    {
        $trunk = new SmsTrunk();
        $trunk->setName('sms_to_email trunk');
        $trunk->setConfiguration(new SmsTrunkConfiguration\SmsToEmail());
        $document = $trunk->save();

        $this->assertTrue($document->hasErrors());
        $this->assertSame(422, $document->getResponse()->getStatusCode());
        $error = $document->getErrors()->first();
        $this->assertSame('configuration - send_to can\'t be blank', $error->getDetail());
        $this->assertSame('/data/attributes/configuration', $error->getSource()->getPointer());
    }

    public function testUpdateNameAndConfiguration()
    {
        $configuration = new SmsTrunkConfiguration\HttpIn();
        $configuration->setUrl('https://example.com/sms/v2');
        $trunk = SmsTrunk::build(self::HTTP_IN_ID);
        $trunk->setName('Webhook');
        $trunk->setConfiguration($configuration);
        $document = $trunk->save();

        $updated = $document->getData();
        $this->assertSame('Webhook', $updated->getName());
        $this->assertSame('https://example.com/sms/v2', $updated->getConfiguration()->getUrl());
        $this->assertSame(SmsHttpMethod::POST, $updated->getConfiguration()->getHttpMethod());
    }

    public function testUpdateFromLoadedDoesNotSendCredentials()
    {
        $trunk = SmsTrunk::find(self::HTTP_OUT_ID)->getData();
        $trunk->getConfiguration()->setCallbackUrl('https://example.com/sms/status/v2');

        $this->assertSame([
            'type' => 'sms_trunks',
            'id' => self::HTTP_OUT_ID,
            'attributes' => [
                'configuration' => [
                    'type' => 'http_out',
                    'attributes' => [
                        'callback_url' => 'https://example.com/sms/status/v2',
                        'allowed_ips' => ['203.0.113.10'],
                    ],
                ],
            ],
        ], $trunk->toJsonApiPatchArray());

        $updated = $trunk->save()->getData();
        $this->assertSame('https://example.com/sms/status/v2', $updated->getConfiguration()->getCallbackUrl());
        $this->assertSame('generated-password', $updated->getConfiguration()->getAuthPassword());
    }

    public function testUpdateFromLoadedWithoutChangesIsClean()
    {
        $trunk = SmsTrunk::find(self::HTTP_OUT_ID)->getData();

        $this->assertSame(['type' => 'sms_trunks', 'id' => self::HTTP_OUT_ID], $trunk->toJsonApiPatchArray());
    }

    public function testUpdateClearRelationships()
    {
        $trunk = SmsTrunk::build(self::HTTP_IN_ID);
        $trunk->setSmsTrunkGroup(null);
        $trunk->setOutgoingDids(new \Swis\JsonApi\Client\Collection());

        $this->assertSame([
            'type' => 'sms_trunks',
            'id' => self::HTTP_IN_ID,
            'relationships' => [
                'sms_trunk_group' => ['data' => null],
                'outgoing_dids' => ['data' => []],
            ],
        ], $trunk->toJsonApiPatchArray());
        $this->assertFalse($trunk->save()->hasErrors());
    }

    public function testUnknownConfigurationIsPreserved()
    {
        $trunk = SmsTrunk::find(self::UNKNOWN_ID)->getData();
        $generic = $trunk->getConfiguration();
        $this->assertInstanceOf(SmsTrunkConfiguration\Generic::class, $generic);
        $this->assertSame('future_transport', $generic->getType());
        $this->assertSame('https://example.com/future', $generic->getAttribute('endpoint'));
        $this->assertSame(3, $generic->getAttribute('retries'));
        $this->assertTrue($generic->getAttribute('enabled'));

        $generic->setAttribute('endpoint', 'https://example.com/future/v2');
        $document = $trunk->save();

        $this->assertFalse($document->hasErrors());
        $this->assertInstanceOf(SmsTrunkConfiguration\Generic::class, $document->getData()->getConfiguration());
    }

    public function testUpdateGenericSendsNulls()
    {
        $trunk = SmsTrunk::build(self::HTTP_IN_ID);
        $trunk->setConfiguration(new SmsTrunkConfiguration\Generic('http_in', ['http_method' => 'get', 'body_type' => null, 'body' => null]));
        $document = $trunk->save();

        $this->assertFalse($document->hasErrors());
    }

    public function testUpdateTypedSendsNulls()
    {
        $configuration = new SmsTrunkConfiguration\HttpIn();
        $configuration->setHttpMethod(SmsHttpMethod::GET);
        $configuration->setBodyType(null);
        $configuration->setBody(null);
        $trunk = SmsTrunk::build(self::HTTP_IN_ID);
        $trunk->setConfiguration($configuration);

        $this->assertSame(
            (new SmsTrunkConfiguration\Generic('http_in', ['http_method' => 'get', 'body_type' => null, 'body' => null]))->toJsonApiArray(),
            $trunk->toJsonApiPatchArray()['attributes']['configuration']
        );
        $this->assertFalse($trunk->save()->hasErrors());
    }

    public function testDelete()
    {
        $document = SmsTrunk::build(self::HTTP_IN_ID)->delete();
        $this->assertFalse($document->hasErrors());
    }

    public function testDeleteInUse()
    {
        $document = SmsTrunk::build('1a2b3c4d-0001-4a5b-8c6d-7e8f9a0b1c01')->delete();
        $this->assertTrue($document->hasErrors());
        $this->assertSame(422, $document->getResponse()->getStatusCode());
        $this->assertSame('base - Cannot delete an SMS trunk while DIDs are routed to it', $document->getErrors()->first()->getDetail());
    }

    public function testRegenerateCredentials()
    {
        $trunk = SmsTrunk::build(self::HTTP_OUT_ID);
        $document = $trunk->regenerateCredentials();

        $this->assertFalse($document->hasErrors());
        $this->assertInstanceOf(\Didww\Item\SmsTrunkRegenerateCredential::class, $document->getData());
        $this->assertSame('3c4d5e6f-0001-4c7d-8e9f-0a1b2c3d4e01', $document->getData()->getId());
    }

    public function testSetters()
    {
        $trunk = new SmsTrunk();
        $trunk->setName('trunk');
        $trunk->setBlocked(true);
        $trunk->setPriority(10);
        $trunk->setAllowAnyDidAsSource(false);
        $trunk->setExternalReferenceId('ref-1');
        $trunk->setPriority(null);

        $this->assertSame([
            'name' => 'trunk',
            'blocked' => true,
            'priority' => null,
            'allow_any_did_as_source' => false,
            'external_reference_id' => 'ref-1',
        ], $trunk->toJsonApiArray()['attributes']);
        $this->assertNull($trunk->getConfiguration());
        $this->assertNull($trunk->getHost());
    }

    public function testFillBuildsConfigurationFromArray()
    {
        $trunk = new SmsTrunk(['configuration' => ['type' => 'smpp_esme', 'attributes' => ['server_location' => 'ams']]]);
        $this->assertInstanceOf(SmsTrunkConfiguration\SmppEsme::class, $trunk->getConfiguration());
        $this->assertSame(SmsServerLocation::AMS, $trunk->getConfiguration()->getServerLocation());

        $this->assertNull((new SmsTrunk(['configuration' => null]))->getConfiguration());

        $this->expectException(\InvalidArgumentException::class);
        (new SmsTrunk())->fill(['configuration' => 'http_in']);
    }

    public function testDebugOutputHidesCredentials()
    {
        $trunk = SmsTrunk::find(self::HTTP_OUT_ID)->getData();

        $dump = print_r($trunk, true);
        $this->assertStringNotContainsString('generated-name', $dump);
        $this->assertStringNotContainsString('generated-password', $dump);
        $this->assertStringContainsString('[FILTERED]', $dump);
        $this->assertStringContainsString('https://example.com/sms/status', $dump);
        $this->assertStringContainsString('https://sms-out.example.com/outbound_messages', $dump);

        ob_start();
        var_dump($trunk);
        $varDump = ob_get_clean();
        $this->assertStringNotContainsString('generated-password', $varDump);

        $info = $trunk->__debugInfo();
        $this->assertSame('sms_trunks', $info['type']);
        $this->assertSame(self::HTTP_OUT_ID, $info['id']);
        $this->assertSame('[FILTERED]', $info['attributes']['configuration']['attributes']['auth_password']);
    }
}
