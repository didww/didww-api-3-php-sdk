<?php

namespace Didww\Tests;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

class AddressVerificationTest extends BaseTest
{
    use ArraySubsetAsserts;

    public function testAllWithIncludesAndPagination()
    {
        $this->startVCR('address_verifications.yml');
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
        $this->stopVCR();
    }

    public function testCreateAddressVerification()
    {
        $this->startVCR('address_verifications.yml');
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

        $this->stopVCR();
    }
}
