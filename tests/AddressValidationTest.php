<?php

namespace Didww\Tests;

class AddressValidationTest extends BaseTest
{
    public function testCreateAddressValidation()
    {
        $this->startVCR('address_validations.yml');
        $address = \Didww\Item\Address::build('d3414687-40f4-4346-a267-c2c65117d28c');
        $requirement = \Didww\Item\Requirement::build('aea92b24-a044-4864-9740-89d3e15b65c7');

        $addressValidation = new \Didww\Item\AddressValidation();
        $addressValidation->setAddress($address);
        $addressValidation->setRequirement($requirement);
        $addressValidationDocument = $addressValidation->save();
        $this->assertFalse($addressValidationDocument->hasErrors());
        $addressValidation = $addressValidationDocument->getData();
        $this->assertInstanceOf('Didww\Item\AddressValidation', $addressValidation);

        $this->stopVCR();
    }

    public function testCreateAddressValidationFailed()
    {
        $this->startVCR('address_validations.yml');
        $identity = \Didww\Item\Identity::build('5e9df058-50d2-4e34-b0d4-d1746b86f41a');
        $address = \Didww\Item\Address::build('d3414687-40f4-4346-a267-c2c65117d28c');
        $requirement = \Didww\Item\Requirement::build('2efc3427-8ba6-4d50-875d-f2de4a068de8');

        $addressValidation = new \Didww\Item\AddressValidation();
        $addressValidation->setIdentity($identity);
        $addressValidation->setAddress($address);
        $addressValidation->setRequirement($requirement);
        $addressValidationDocument = $addressValidation->save();
        $this->assertTrue($addressValidationDocument->hasErrors());

        $this->stopVCR();
    }
}
