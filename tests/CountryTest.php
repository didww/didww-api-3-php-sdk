<?php

namespace Didww\Tests;

class CountryTest extends BaseTest
{
    public function testAll()
    {
        $this->startVCR('countries.yml');

        $countriesDocument = \Didww\Item\Country::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Country', $countriesDocument->getData());

        $this->stopVCR();
    }

    public function testFind()
    {
        $this->startVCR('countries.yml');

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

        $this->stopVCR();
    }
}
