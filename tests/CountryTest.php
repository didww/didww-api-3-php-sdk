<?php

namespace Didww\Tests;

class CountryTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'countries.yml';
    }

    public function testAll()
    {
        $countriesDocument = \Didww\Item\Country::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Country', $countriesDocument->getData());
    }

    public function testFindWithRegions()
    {
        $uuid = '661d8448-8897-4765-acda-00cc1740148d';
        $countryDocument = \Didww\Item\Country::find($uuid, ['include' => 'regions']);
        $country = $countryDocument->getData();
        $this->assertInstanceOf('Didww\Item\Country', $country);
        $this->assertEquals('Lithuania', $country->getName());
        $this->assertEquals('LT', $country->getIso());

        $regions = $country->regions()->getIncluded();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Region', $regions->all());
        $this->assertCount(10, $regions->all());
        $this->assertEquals('Alytaus Apskritis', $regions->all()[0]->getName());
    }

    public function testFind()
    {
        $uuid = '7eda11bb-0e66-4146-98e7-57a5281f56c8';
        $countryDocument = \Didww\Item\Country::find($uuid);
        $country = $countryDocument->getData();
        $this->assertInstanceOf('Didww\Item\Country', $country);
        $this->assertEquals($country->getAttributes(), [
            'iso' => 'GB',
            'prefix' => '44',
            'name' => 'United Kingdom',
        ]);

        $this->assertEquals('GB', $country->getIso());
        $this->assertEquals('44', $country->getPrefix());
        $this->assertEquals('United Kingdom', $country->getName());
    }
}
