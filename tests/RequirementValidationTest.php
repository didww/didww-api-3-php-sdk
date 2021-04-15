<?php

namespace Didww\Tests;

class RequirementValidationTest extends BaseTest
{
    public function testCreateRequirementValidation()
    {
        $this->startVCR('requirement_validations.yml');
        $address = \Didww\Item\Address::build('d3414687-40f4-4346-a267-c2c65117d28c');
        $requirement = \Didww\Item\Requirement::build('aea92b24-a044-4864-9740-89d3e15b65c7');

        $requirementValidation = new \Didww\Item\RequirementValidation();
        $requirementValidation->setAddress($address);
        $requirementValidation->setRequirement($requirement);
        $requirementValidationDocument = $requirementValidation->save();
        $this->assertFalse($requirementValidationDocument->hasErrors());
        $requirementValidation = $requirementValidationDocument->getData();
        $this->assertInstanceOf('Didww\Item\RequirementValidation', $requirementValidation);

        $this->stopVCR();
    }

    public function testCreateRequirementValidationFailed()
    {
        $this->startVCR('requirement_validations.yml');
        $identity = \Didww\Item\Identity::build('5e9df058-50d2-4e34-b0d4-d1746b86f41a');
        $address = \Didww\Item\Address::build('d3414687-40f4-4346-a267-c2c65117d28c');
        $requirement = \Didww\Item\Requirement::build('2efc3427-8ba6-4d50-875d-f2de4a068de8');

        $requirementValidation = new \Didww\Item\RequirementValidation();
        $requirementValidation->setIdentity($identity);
        $requirementValidation->setAddress($address);
        $requirementValidation->setRequirement($requirement);
        $requirementValidationDocument = $requirementValidation->save();
        $this->assertTrue($requirementValidationDocument->hasErrors());

        $this->stopVCR();
    }
}
