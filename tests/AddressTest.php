<?php

namespace Didww\Tests;

class AddressTest extends BaseTest
{
    public function testAllWithIncludesAndPagination()
    {
        $this->startVCR('addresses.yml');
        $addressesDocument = \Didww\Item\Address::all(
            [
                'include' => 'country,identity,proofs',
                'page' => ['size' => 5, 'number' => 1],
            ]
        );
        $addresses = $addressesDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Address', $addresses);

        $country = $addresses[0]->country()->getIncluded();
        $this->assertInstanceOf('Didww\Item\Country', $country);

        $this->assertEquals(2, $addressesDocument->getMeta()['total_records']);
        $this->stopVCR();
    }

    public function testCreateAddress()
    {
        $this->startVCR('addresses.yml');
        $attributes = [
            'city_name' => 'New York',
            'postal_code' => '123',
            'address' => 'some street',
            'description' => 'test address',
        ];
        $identity = \Didww\Item\Identity::build('5e9df058-50d2-4e34-b0d4-d1746b86f41a');
        $country = \Didww\Item\Country::build('1f6fc2bd-f081-4202-9b1a-d9cb88d942b9');

        $address = new \Didww\Item\Address($attributes);
        $address->setCountry($country);
        $address->setIdentity($identity);
        $this->assertArraySubset($attributes, $address->getAttributes());
        $addressDocument = $address->save(['include' => 'country']);
        $address = $addressDocument->getData();
        $this->assertArraySubset($attributes, $address->getAttributes());
        $this->assertInstanceOf('Didww\Item\Address', $address);
        $this->assertInstanceOf('Didww\Item\Country', $address->country()->getIncluded());

        $this->stopVCR();
    }

    public function testUpdateAddress()
    {
        $this->startVCR('addresses.yml');
        $attributes = [
            'city_name' => 'Chicago',
            'postal_code' => '1234',
            'address' => 'Main street',
            'description' => 'some address',
        ];

        $address = \Didww\Item\Address::build('bf69bc70-e1c2-442c-9f30-335ee299b663');
        $address->fill($attributes);
        $addressDocument = $address->save();
        $address = $addressDocument->getData();
        $this->assertInstanceOf('Didww\Item\Address', $address);
        $this->assertArraySubset($attributes, $address->getAttributes());
        $this->stopVCR();
    }

    public function testDeleteAddress()
    {
        $this->startVCR('addresses.yml');

        $address = \Didww\Item\Address::build('bf69bc70-e1c2-442c-9f30-335ee299b663');

        $addressDocument = $address->delete();

        $this->assertFalse($addressDocument->hasErrors());
        $this->stopVCR();
    }
}
