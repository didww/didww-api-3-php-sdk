<?php

namespace Didww\Tests;

class EmergencyRequirementValidationTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'emergency_requirement_validations.yml';
    }

    public function testCreateEmergencyRequirementValidation()
    {
        $address = \Didww\Item\Address::build('d3414687-40f4-4346-a267-c2c65117d28c');
        $identity = \Didww\Item\Identity::build('5e9df058-50d2-4e34-b0d4-d1746b86f41a');
        $emergencyRequirement = \Didww\Item\EmergencyRequirement::build('aea92b24-a044-4864-9740-89d3e15b65c7');

        $validation = new \Didww\Item\EmergencyRequirementValidation();
        $validation->setAddress($address);
        $validation->setIdentity($identity);
        $validation->setEmergencyRequirement($emergencyRequirement);
        $document = $validation->save();
        $this->assertFalse($document->hasErrors());
        $data = $document->getData();
        $this->assertInstanceOf('Didww\Item\EmergencyRequirementValidation', $data);
    }

    public function testCreateEmergencyRequirementValidation204()
    {
        $address = \Didww\Item\Address::build('aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee');
        $identity = \Didww\Item\Identity::build('11111111-aaaa-bbbb-cccc-dddddddddddd');
        $emergencyRequirement = \Didww\Item\EmergencyRequirement::build('22222222-3333-4444-5555-666666666666');

        $validation = new \Didww\Item\EmergencyRequirementValidation();
        $validation->setAddress($address);
        $validation->setIdentity($identity);
        $validation->setEmergencyRequirement($emergencyRequirement);
        $document = $validation->save();
        $this->assertFalse($document->hasErrors());
    }

    public function testEndpoint()
    {
        $this->assertEquals(
            '/emergency_requirement_validations',
            \Didww\Item\EmergencyRequirementValidation::getEndpoint()
        );
    }
}
