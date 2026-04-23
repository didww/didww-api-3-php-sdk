<?php

namespace Didww\Tests;

class EmergencyRequirementTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'emergency_requirements.yml';
    }

    public function testAllEmergencyRequirements()
    {
        $document = \Didww\Item\EmergencyRequirement::all();
        $data = $document->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\EmergencyRequirement', $data);
        $this->assertCount(1, $data);

        $first = $data[0];
        $this->assertEquals('personal', $first->getIdentityType());
        $this->assertEquals('city', $first->getAddressAreaLevel());
        $this->assertEquals('city', $first->getPersonalAreaLevel());
        $this->assertEquals('city', $first->getBusinessAreaLevel());
        $this->assertEquals(['street', 'city', 'postal_code'], $first->getAddressMandatoryFields());
        $this->assertEquals(['first_name', 'last_name'], $first->getPersonalMandatoryFields());
        $this->assertEquals(['company_name', 'tax_number'], $first->getBusinessMandatoryFields());
        $this->assertEquals('7-14 days', $first->getEstimateSetupTime());
        $this->assertNull($first->getRequirementRestrictionMessage());
    }

    public function testFindEmergencyRequirement()
    {
        $uuid = '01234567-89ab-cdef-0123-456789abcdef';
        $document = \Didww\Item\EmergencyRequirement::find($uuid);

        $data = $document->getData();
        $this->assertInstanceOf('Didww\Item\EmergencyRequirement', $data);
        $this->assertEquals($uuid, $data->getId());
        $this->assertEquals('business', $data->getIdentityType());
        $this->assertEquals('7-14 days', $data->getEstimateSetupTime());
        $this->assertEquals(
            'Additional compliance review is required for this country.',
            $data->getRequirementRestrictionMessage()
        );
    }
}
