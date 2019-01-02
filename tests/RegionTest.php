<?php

namespace Didww\Tests;

class RegionTest extends BaseTest
{
    public function testAll()
    {
        $this->startVCR('regions.yml');

        $regionsDocument = \Didww\Item\Region::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Region', $regionsDocument->getData());

        $this->stopVCR();
    }

    public function testFind()
    {
        $this->startVCR('regions.yml');

        $uuid = 'c11b1f34-16cf-4ba6-8497-f305b53d5b01';
        $regionDocument = \Didww\Item\Region::find($uuid, ['include' => 'country']);
        $countryRelation = $regionDocument->getData()->country();

        $this->assertInstanceOf('Didww\Item\Region', $regionDocument->getData());
        $this->assertEquals($regionDocument->getData()->getAttributes(), [
            'name' => 'California',
        ]);
        $this->assertInstanceOf('Didww\Item\Country', $countryRelation->getIncluded());
        $this->assertEquals($countryRelation->getIncluded()->getAttributes(), [
            'iso' => 'US',
            'prefix' => '1',
            'name' => 'United States',
        ]);

        $this->stopVCR();
    }
}
