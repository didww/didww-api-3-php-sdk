<?php

namespace Didww\Tests;

class EmergencyCallingServiceTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'emergency_calling_services.yml';
    }

    public function testAllEmergencyCallingServices()
    {
        $document = \Didww\Item\EmergencyCallingService::all();
        $data = $document->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\EmergencyCallingService', $data);
        $this->assertCount(1, $data);

        $first = $data[0];
        $this->assertEquals('London Office ECS', $first->getName());
        $this->assertEquals('ECS-0001', $first->getReference());
        $this->assertEquals('active', $first->getStatus());
        $this->assertInstanceOf(\DateTime::class, $first->getActivatedAt());
        $this->assertNull($first->getCanceledAt());
        $this->assertInstanceOf(\DateTime::class, $first->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $first->getRenewDate());
    }

    public function testFindEmergencyCallingService()
    {
        $uuid = '01234567-89ab-cdef-0123-456789abcdef';
        $document = \Didww\Item\EmergencyCallingService::find($uuid);

        $data = $document->getData();
        $this->assertInstanceOf('Didww\Item\EmergencyCallingService', $data);
        $this->assertEquals($uuid, $data->getId());
        $this->assertEquals('Berlin Office ECS', $data->getName());
        $this->assertEquals('ECS-0042', $data->getReference());
        $this->assertEquals('pending update', $data->getStatus());
    }

    public function testFindEmergencyCallingServiceWithIncludes()
    {
        $uuid = '01234567-89ab-cdef-0123-456789abcdef';
        $document = \Didww\Item\EmergencyCallingService::find($uuid, ['include' => 'emergency_requirement,emergency_verification']);

        $data = $document->getData();
        $this->assertInstanceOf('Didww\Item\EmergencyCallingService', $data);
        $this->assertEquals($uuid, $data->getId());

        $emergencyRequirement = $data->emergencyRequirement()->getIncluded();
        $this->assertInstanceOf('Didww\Item\EmergencyRequirement', $emergencyRequirement);
        $this->assertEquals('44444444-3333-2222-1111-000000000000', $emergencyRequirement->getId());
        $this->assertEquals('personal', $emergencyRequirement->getIdentityType());
        $this->assertEquals('city', $emergencyRequirement->getAddressAreaLevel());
        $this->assertEquals('country', $emergencyRequirement->getPersonalAreaLevel());
        $this->assertEquals('country', $emergencyRequirement->getBusinessAreaLevel());
        $this->assertEquals(['Street', 'Building Number', 'Zip Code', 'City'], $emergencyRequirement->getAddressMandatoryFields());
        $this->assertEquals(['First Name', 'Last Name'], $emergencyRequirement->getPersonalMandatoryFields());
        $this->assertEquals(['Company Name'], $emergencyRequirement->getBusinessMandatoryFields());
        $this->assertEquals(3, $emergencyRequirement->getEstimateSetupTime());
        $this->assertNull($emergencyRequirement->getRequirementRestrictionMessage());

        $emergencyVerification = $data->emergencyVerification()->getIncluded();
        $this->assertInstanceOf('Didww\Item\EmergencyVerification', $emergencyVerification);
        $this->assertEquals('77777777-6666-5555-4444-333333333333', $emergencyVerification->getId());
        $this->assertEquals('EV-0099', $emergencyVerification->getReference());
        $this->assertEquals(\Didww\Enum\EmergencyVerificationStatus::APPROVED, $emergencyVerification->getStatus());
        $this->assertTrue($emergencyVerification->isApproved());
        $this->assertFalse($emergencyVerification->isPending());
        $this->assertNull($emergencyVerification->getRejectReasons());
        $this->assertNull($emergencyVerification->getRejectComment());
        $this->assertInstanceOf(\DateTime::class, $emergencyVerification->getCreatedAt());
    }

    public function testDeleteEmergencyCallingService()
    {
        $uuid = '01234567-89ab-cdef-0123-456789abcdef';
        $ecs = \Didww\Item\EmergencyCallingService::build($uuid);
        $document = $ecs->delete();
        $this->assertFalse($document->hasErrors());
    }
}
