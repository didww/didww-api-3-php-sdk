<?php

namespace Didww\Tests;

class AddressRequirementValidationTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'address_requirement_validations.yml';
    }

    public function testCreateAddressRequirementValidation()
    {
        $address = \Didww\Item\Address::build('d3414687-40f4-4346-a267-c2c65117d28c');
        $requirement = \Didww\Item\AddressRequirement::build('aea92b24-a044-4864-9740-89d3e15b65c7');

        $requirementValidation = new \Didww\Item\AddressRequirementValidation();
        $requirementValidation->setAddress($address);
        $requirementValidation->setAddressRequirement($requirement);
        $requirementValidationDocument = $requirementValidation->save();
        $this->assertFalse($requirementValidationDocument->hasErrors());
        $requirementValidation = $requirementValidationDocument->getData();
        $this->assertInstanceOf('Didww\Item\AddressRequirementValidation', $requirementValidation);
    }

    public function testCreateAddressRequirementValidationFailed()
    {
        $identity = \Didww\Item\Identity::build('5e9df058-50d2-4e34-b0d4-d1746b86f41a');
        $address = \Didww\Item\Address::build('d3414687-40f4-4346-a267-c2c65117d28c');
        $requirement = \Didww\Item\AddressRequirement::build('2efc3427-8ba6-4d50-875d-f2de4a068de8');

        $requirementValidation = new \Didww\Item\AddressRequirementValidation();
        $requirementValidation->setIdentity($identity);
        $requirementValidation->setAddress($address);
        $requirementValidation->setAddressRequirement($requirement);
        $requirementValidationDocument = $requirementValidation->save();
        $this->assertTrue($requirementValidationDocument->hasErrors());
    }

    public function testCreateAddressRequirementValidation204()
    {
        $address = \Didww\Item\Address::build('aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee');
        $requirement = \Didww\Item\AddressRequirement::build('11111111-2222-3333-4444-555555555555');

        $validation = new \Didww\Item\AddressRequirementValidation();
        $validation->setAddress($address);
        $validation->setAddressRequirement($requirement);
        $document = $validation->save();
        $this->assertFalse($document->hasErrors());
    }

    public function testEndpoint()
    {
        $this->assertEquals('/address_requirement_validations', \Didww\Item\AddressRequirementValidation::getEndpoint());
    }
}
