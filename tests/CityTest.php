<?php

namespace Didww\Tests;

class CityTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'cities.yml';
    }
    public function testAll()
    {

        $citiesDocument = \Didww\Item\City::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\City', $citiesDocument->getData());

    }

    public function testFind()
    {

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

    }
}
