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
        $this->assertEquals('country', $first->getPersonalAreaLevel());
        $this->assertNull($first->getBusinessAreaLevel());
        $this->assertEquals(['street', 'city', 'postal_code'], $first->getAddressMandatoryFields());
        $this->assertEquals(['first_name', 'last_name'], $first->getPersonalMandatoryFields());
        $this->assertEquals([], $first->getBusinessMandatoryFields());
        $this->assertEquals('7-14 days', $first->getEstimateSetupTime());
        $this->assertNull($first->getRequirementRestrictionMessage());
        $this->assertSame('0.0', $first->getMeta()['setup_price']);
        $this->assertSame('0.75', $first->getMeta()['monthly_price']);
    }

    public function testFindEmergencyRequirement()
    {
        $uuid = '01234567-89ab-cdef-0123-456789abcdef';
        $document = \Didww\Item\EmergencyRequirement::find($uuid);

        $data = $document->getData();
        $this->assertInstanceOf('Didww\Item\EmergencyRequirement', $data);
        $this->assertEquals($uuid, $data->getId());
        $this->assertEquals('business', $data->getIdentityType());
        $this->assertEquals('area', $data->getAddressAreaLevel());
        $this->assertNull($data->getPersonalAreaLevel());
        $this->assertEquals('world_wide', $data->getBusinessAreaLevel());
        $this->assertEquals('7-14 days', $data->getEstimateSetupTime());
        $this->assertEquals(
            'Additional compliance review is required for this country.',
            $data->getRequirementRestrictionMessage()
        );
        $this->assertSame('0.0', $data->getMeta()['setup_price']);
        $this->assertSame('2.5', $data->getMeta()['monthly_price']);
    }
}
