<?php

namespace Didww\Tests;

use Didww\Enum\AreaLevel;
use Didww\Enum\IdentityType;

class AddressRequirementTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'address_requirements.yml';
    }

    public function testAllWithIncludesAndPagination()
    {
        $requirementsDocument = \Didww\Item\AddressRequirement::all(
            ['page' => ['size' => 5, 'number' => 1]]
        );
        $requirements = $requirementsDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\AddressRequirement', $requirements);

        $this->assertEquals(73, $requirementsDocument->getMeta()['total_records']);

        $businessOnly = $requirements[0];
        $this->assertEquals(IdentityType::BUSINESS, $businessOnly->getIdentityType());
        $this->assertNull($businessOnly->getPersonalAreaLevel());
        $this->assertEquals(AreaLevel::WORLDWIDE, $businessOnly->getBusinessAreaLevel());
        $this->assertSame([], $businessOnly->getBusinessMandatoryFields());
    }

    public function testFindWithIncludes()
    {
        $includes = [
            'country',
            'personal_permanent_document',
            'business_permanent_document',
            'personal_onetime_document',
            'business_onetime_document',
            'personal_proof_types',
            'business_proof_types',
            'address_proof_types',
        ];
        $requirementsDocument = \Didww\Item\AddressRequirement::find(
            '25d12afe-1ec6-4fe3-9621-b250dd1fb959',
            [
                'include' => join(',', $includes),
                'page' => ['size' => 5, 'number' => 1],
            ]
        );
        $requirement = $requirementsDocument->getData();
        $this->assertInstanceOf('Didww\Item\AddressRequirement', $requirement);
        $this->assertEquals([
            'identity_type' => 'any',
            'personal_area_level' => 'world_wide',
            'business_area_level' => 'world_wide',
            'address_area_level' => 'world_wide',
            'personal_proof_qty' => 1,
            'business_proof_qty' => 1,
            'address_proof_qty' => 1,
            'personal_mandatory_fields' => ['birth_date', 'country', 'personal_tax_id'],
            'business_mandatory_fields' => ['id_number', 'tax_id', 'country', 'company_reg_number', 'personal_tax_id'],
            'service_description_required' => true,
            'restriction_message' => 'End User Registration is Required',
            'number_type' => 'local',
        ], $requirement->getAttributes());
        $this->assertSame(\Didww\Enum\NumberType::LOCAL, $requirement->getNumberType());

        // Typed getter assertions
        $this->assertEquals(AreaLevel::WORLDWIDE, $requirement->getPersonalAreaLevel());
        $this->assertEquals(AreaLevel::WORLDWIDE, $requirement->getBusinessAreaLevel());
        $this->assertEquals(AreaLevel::WORLDWIDE, $requirement->getAddressAreaLevel());
        $this->assertEquals(1, $requirement->getPersonalProofQty());
        $this->assertEquals(1, $requirement->getBusinessProofQty());
        $this->assertEquals(1, $requirement->getAddressProofQty());
        $this->assertSame(['birth_date', 'country', 'personal_tax_id'], $requirement->getPersonalMandatoryFields());
        $this->assertSame(['id_number', 'tax_id', 'country', 'company_reg_number', 'personal_tax_id'], $requirement->getBusinessMandatoryFields());
        $this->assertTrue($requirement->getServiceDescriptionRequired());
        $this->assertEquals('End User Registration is Required', $requirement->getRestrictionMessage());

        // Relationship method assertions
        $this->assertNotNull($requirement->country());
        $this->assertNotNull($requirement->personalPermanentDocument());
        $this->assertNotNull($requirement->businessPermanentDocument());
        $this->assertNotNull($requirement->personalOnetimeDocument());
        $this->assertNotNull($requirement->businessOnetimeDocument());
        $this->assertNotNull($requirement->personalProofTypes());
        $this->assertNotNull($requirement->businessProofTypes());
        $this->assertNotNull($requirement->addressProofTypes());
        $this->assertSame('address', $requirement->addressProofTypes()->getIncluded()->first()->getEntityType());
        $this->assertSame('personal', $requirement->personalProofTypes()->getIncluded()->first()->getEntityType());
    }

    public function testMandatoryFieldsWithoutTheAttributesAreEmpty()
    {
        $requirement = new \Didww\Item\AddressRequirement();

        $this->assertSame([], $requirement->getPersonalMandatoryFields());
        $this->assertSame([], $requirement->getBusinessMandatoryFields());
    }

    public function testEndpoint()
    {
        $this->assertEquals('/address_requirements', \Didww\Item\AddressRequirement::getEndpoint());
    }
}
