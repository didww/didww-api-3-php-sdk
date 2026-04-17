<?php

namespace Didww\Tests;

class EmergencyVerificationTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'emergency_verifications.yml';
    }

    public function testAllEmergencyVerifications()
    {
        $document = \Didww\Item\EmergencyVerification::all();
        $data = $document->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\EmergencyVerification', $data);
        $this->assertCount(1, $data);

        $first = $data[0];
        $this->assertEquals('EV-0001', $first->getReference());
        $this->assertEquals('pending', $first->getStatus());
        $this->assertNull($first->getRejectReasons());
        $this->assertNull($first->getRejectComment());
        $this->assertEquals('https://example.com/emergency/hook', $first->getCallbackUrl());
        $this->assertEquals('POST', $first->getCallbackMethod());
        $this->assertNull($first->getExternalReferenceId());
        $this->assertInstanceOf(\DateTime::class, $first->getCreatedAt());
    }

    public function testFindEmergencyVerification()
    {
        $uuid = '01234567-89ab-cdef-0123-456789abcdef';
        $document = \Didww\Item\EmergencyVerification::find($uuid);

        $data = $document->getData();
        $this->assertInstanceOf('Didww\Item\EmergencyVerification', $data);
        $this->assertEquals($uuid, $data->getId());
        $this->assertEquals('rejected', $data->getStatus());
        $this->assertEquals(
            ['Address does not match identity', 'Missing proof of occupancy'],
            $data->getRejectReasons()
        );
        $this->assertEquals('Please re-submit with updated documentation.', $data->getRejectComment());
        $this->assertEquals('ref-xyz-999', $data->getExternalReferenceId());
    }

    public function testCreateEmergencyVerification()
    {
        $ecs = \Didww\Item\EmergencyCallingService::build('33333333-4444-5555-6666-777777777777');
        $address = \Didww\Item\Address::build('88888888-9999-aaaa-bbbb-cccccccccccc');
        $did = \Didww\Item\Did::build('11111111-aaaa-bbbb-cccc-dddddddddddd');
        $dids = new \Swis\JsonApi\Client\Collection([$did]);

        $verification = new \Didww\Item\EmergencyVerification();
        $verification->setCallbackUrl('https://example.com/emergency/hook');
        $verification->setCallbackMethod('POST');
        $verification->setExternalReferenceId('ref-abc-123');
        $verification->setEmergencyCallingService($ecs);
        $verification->setAddress($address);
        $verification->setDids($dids);
        $document = $verification->save();

        $data = $document->getData();
        $this->assertInstanceOf('Didww\Item\EmergencyVerification', $data);
        $this->assertEquals('pending', $data->getStatus());
        $this->assertEquals('ref-abc-123', $data->getExternalReferenceId());
    }
}
