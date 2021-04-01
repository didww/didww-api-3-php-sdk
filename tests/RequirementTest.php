<?php

namespace Didww\Tests;

class RequirementTest extends BaseTest
{
    public function testAllWithIncludesAndPagination()
    {
        $this->startVCR('requirements.yml');
        $requirementsDocument = \Didww\Item\Requirement::all(
            ['page' => ['size' => 5, 'number' => 1]]
        );
        $requirements = $requirementsDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Requirement', $requirements);

        $this->assertEquals(73, $requirementsDocument->getMeta()['total_records']);
        $this->stopVCR();
    }

    public function testFindWithIncludes()
    {
        $this->startVCR('requirements.yml');
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

        $this->stopVCR();
    }
}
