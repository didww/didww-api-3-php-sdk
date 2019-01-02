<?php

namespace Didww\Tests;

class CountryTest extends BaseTest
{
    public function testAll()
    {
        $this->startVCR('countries.yml');

        $countries = \Didww\Item\Country::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Country', $countries->getData());

        $this->stopVCR();
    }

    public function testFind()
    {
        $this->startVCR('countries.yml');

        $uuid = '7eda11bb-0e66-4146-98e7-57a5281f56c8';
        $country = \Didww\Item\Country::find($uuid);
        $this->assertInstanceOf('Didww\Item\Country', $country->getData());
        $this->assertEquals($country->getData()->getAttributes(), [
            'iso' => 'GB',
            'prefix' => '44',
            'name' => 'United Kingdom',
        ]);

        $this->stopVCR();
    }
}
