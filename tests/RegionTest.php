<?php

namespace Didww\Tests;

class RegionTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'regions.yml';
    }

    public function testAll()
    {
        $regionsDocument = \Didww\Item\Region::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Region', $regionsDocument->getData());
    }

    public function testFind()
    {
        $uuid = 'c11b1f34-16cf-4ba6-8497-f305b53d5b01';
        $regionDocument = \Didww\Item\Region::find($uuid, ['include' => 'country']);
        $countryRelation = $regionDocument->getData()->country();

        $region = $regionDocument->getData();
        $this->assertInstanceOf('Didww\Item\Region', $region);
        $this->assertEquals('California', $region->getName());
        $this->assertEquals('US-CA', $region->getIso());
        $this->assertInstanceOf('Didww\Item\Country', $countryRelation->getIncluded());
        $this->assertEquals($countryRelation->getIncluded()->getAttributes(), [
            'iso' => 'US',
            'prefix' => '1',
            'name' => 'United States',
        ]);
    }
}
