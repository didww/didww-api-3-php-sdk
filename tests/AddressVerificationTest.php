<?php

namespace Didww\Tests;

use Didww\Enum\AddressVerificationStatus;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

class AddressVerificationTest extends CassetteTest
{
    use ArraySubsetAsserts;

    protected function getCassetteName(): string
    {
        return 'address_verifications.yml';
    }

    public function testAllWithIncludesAndPagination()
    {
        $addressVerificationVerificationsDocument = \Didww\Item\AddressVerification::all(
            [
                'include' => 'address,dids',
                'page' => ['size' => 5, 'number' => 1],
            ]
        );
        $addressVerificationVerifications = $addressVerificationVerificationsDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\AddressVerification', $addressVerificationVerifications);

        $address = $addressVerificationVerifications[0]->address()->getIncluded();
        $this->assertInstanceOf('Didww\Item\Address', $address);

        $this->assertEquals(1, $addressVerificationVerificationsDocument->getMeta()['total_records']);
    }

    public function testFindAddressVerification()
    {
        $addressVerificationDocument = \Didww\Item\AddressVerification::find('c8e004b0-87ec-4987-b4fb-ee89db099f0e');
        $addressVerification = $addressVerificationDocument->getData();
        $this->assertInstanceOf('Didww\Item\AddressVerification', $addressVerification);
        $this->assertEquals('c8e004b0-87ec-4987-b4fb-ee89db099f0e', $addressVerification->getId());
        $this->assertEquals(AddressVerificationStatus::APPROVED, $addressVerification->getStatus());
        $this->assertEquals('SHB-485120', $addressVerification->getReference());
    }

    public function testFindRejectedAddressVerification()
    {
        $addressVerificationDocument = \Didww\Item\AddressVerification::find('429e6d4e-2ee9-4953-aa98-0b3ac07f0f96');
        $addressVerification = $addressVerificationDocument->getData();
        $this->assertInstanceOf('Didww\Item\AddressVerification', $addressVerification);
        $this->assertEquals('429e6d4e-2ee9-4953-aa98-0b3ac07f0f96', $addressVerification->getId());
        $this->assertEquals(AddressVerificationStatus::REJECTED, $addressVerification->getStatus());
        $this->assertEquals(['Address cannot be validated', 'Proof of address should be not older than of 6 months'], $addressVerification->getRejectReasons());
        $this->assertEquals('ODW-879912', $addressVerification->getReference());
    }

    public function testNullableGettersReturnNullOnEmptyObject()
    {
        $av = new \Didww\Item\AddressVerification();
        $this->assertNull($av->getServiceDescription());
        $this->assertNull($av->getCallbackUrl());
        $this->assertNull($av->getCallbackMethod());
        $this->assertNull($av->getRejectReasons());
        $this->assertNull($av->getReference());
    }

    public function testCreateAddressVerification()
    {
        $attributes = [
            'callback_url' => 'http://example.com',
            'callback_method' => 'GET',
        ];
        $address = \Didww\Item\Address::build('d3414687-40f4-4346-a267-c2c65117d28c');
        $dids = new \Swis\JsonApi\Client\Collection([
            \Didww\Item\Did::build('a9d64c02-4486-4acb-a9a1-be4c81ff0659'),
        ]);

        $addressVerification = new \Didww\Item\AddressVerification($attributes);
        $this->assertArraySubset($attributes, $addressVerification->getAttributes());
        $addressVerification->setAddress($address);
        $addressVerification->setDids($dids);
        $addressVerificationDocument = $addressVerification->save(['include' => 'address']);
        $addressVerification = $addressVerificationDocument->getData();
        $this->assertArraySubset($attributes, $addressVerification->getAttributes());
        $this->assertInstanceOf('Didww\Item\AddressVerification', $addressVerification);
        $this->assertInstanceOf('Didww\Item\Address', $addressVerification->address()->getIncluded());
    }
}
