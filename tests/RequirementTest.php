<?php

namespace Didww\Tests;

use Didww\Enum\AreaLevel;

class RequirementTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'requirements.yml';
    }
    public function testAllWithIncludesAndPagination()
    {
        $requirementsDocument = \Didww\Item\Requirement::all(
            ['page' => ['size' => 5, 'number' => 1]]
        );
        $requirements = $requirementsDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Requirement', $requirements);

        $this->assertEquals(73, $requirementsDocument->getMeta()['total_records']);
    }

    public function testFindWithIncludes()
    {
        $includes = [
            'country',
            'did_group_type',
            'personal_permanent_document',
            'business_permanent_document',
            'personal_onetime_document',
            'business_onetime_document',
            'personal_proof_types',
            'business_proof_types',
            'address_proof_types',
        ];
        $requirementsDocument = \Didww\Item\Requirement::find(
            '25d12afe-1ec6-4fe3-9621-b250dd1fb959',
            [
                'include' => join(',', $includes),
                'page' => ['size' => 5, 'number' => 1],
            ]
        );
        $requirement = $requirementsDocument->getData();
        $this->assertInstanceOf('Didww\Item\Requirement', $requirement);
        $this->assertEquals([
            'identity_type' => 'Any',
            'personal_area_level' => 'WorldWide',
            'business_area_level' => 'WorldWide',
            'address_area_level' => 'WorldWide',
            'personal_proof_qty' => 1,
            'business_proof_qty' => 1,
            'address_proof_qty' => 1,
            'personal_mandatory_fields' => ['Birth Date', 'Country', 'Personal Tax ID'],
            'business_mandatory_fields' => ['Proof of ID', 'VAT Number / TAX Code', 'Country', 'Company ID', 'Representative Tax ID'],
            'service_description_required' => true,
            'restriction_message' => 'End User Registration is Required',
        ], $requirement->getAttributes());

        // Typed getter assertions
        $this->assertEquals(AreaLevel::WORLDWIDE, $requirement->getPersonalAreaLevel());
        $this->assertEquals(AreaLevel::WORLDWIDE, $requirement->getBusinessAreaLevel());
        $this->assertEquals(AreaLevel::WORLDWIDE, $requirement->getAddressAreaLevel());
        $this->assertEquals(1, $requirement->getPersonalProofQty());
        $this->assertEquals(1, $requirement->getBusinessProofQty());
        $this->assertEquals(1, $requirement->getAddressProofQty());
        $this->assertEquals(['Birth Date', 'Country', 'Personal Tax ID'], $requirement->getPersonalMandatoryFields());
        $this->assertEquals(['Proof of ID', 'VAT Number / TAX Code', 'Country', 'Company ID', 'Representative Tax ID'], $requirement->getBusinessMandatoryFields());
        $this->assertTrue($requirement->getServiceDescriptionRequired());
        $this->assertEquals('End User Registration is Required', $requirement->getRestrictionMessage());

        // Relationship method assertions
        $this->assertNotNull($requirement->country());
        $this->assertNotNull($requirement->didGroupType());
        $this->assertNotNull($requirement->personalPermanentDocument());
        $this->assertNotNull($requirement->businessPermanentDocument());
        $this->assertNotNull($requirement->personalOnetimeDocument());
        $this->assertNotNull($requirement->businessOnetimeDocument());
        $this->assertNotNull($requirement->personalProofTypes());
        $this->assertNotNull($requirement->businessProofTypes());
        $this->assertNotNull($requirement->addressProofTypes());

    }
}
