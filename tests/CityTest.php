<?php

namespace Didww\Tests;

class CityTest extends BaseTest
{
    public function testAll()
    {
        $this->startVCR('cities.yml');

        $citiesDocument = \Didww\Item\City::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\City', $citiesDocument->getData());

        $this->stopVCR();
    }

    public function testFind()
    {
        $this->startVCR('cities.yml');

        $uuid = '368bf92f-c36e-473f-96fc-d53ed1b4028b';
        $cityDocument = \Didww\Item\City::find($uuid, ['include' => 'country,region,area']);
        $countryRelation = $cityDocument->getData()->country();
        $regionRelation = $cityDocument->getData()->region();
        $areaRelation = $cityDocument->getData()->area();

        $this->assertInstanceOf('Didww\Item\City', $cityDocument->getData());
        $this->assertEquals([
            'name' => 'New York',
        ], $cityDocument->getData()->getAttributes());
        $this->assertInstanceOf('Didww\Item\Country', $countryRelation->getIncluded());
        $this->assertEquals([
            'iso' => 'US',
            'prefix' => '1',
            'name' => 'United States',
        ], $countryRelation->getIncluded()->getAttributes());
        $this->assertInstanceOf('Didww\Item\Region', $regionRelation->getIncluded());
        $this->assertEquals([
            'name' => 'New York',
        ], $regionRelation->getIncluded()->getAttributes());
        $this->assertEquals(null, $areaRelation->getIncluded());

        $this->stopVCR();
    }
}
