<?php

namespace Didww\Tests;

class AreaTest extends BaseTest
{
    public function testAll()
    {
        $this->startVCR('areas.yml');

        $areasDocument = \Didww\Item\Area::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Area', $areasDocument->getData());

        $this->stopVCR();
    }

    public function testFind()
    {
        $this->startVCR('areas.yml');

        $uuid = 'ab2adc18-7c94-42d9-bdde-b28dfc373a22';
        $areaDocument = \Didww\Item\Area::find($uuid, ['include' => 'country']);
        $countryRelation = $areaDocument->getData()->country();

        $this->assertInstanceOf('Didww\Item\Area', $areaDocument->getData());
        $this->assertEquals([
            'name' => 'Tuscany',
        ], $areaDocument->getData()->getAttributes());
        $this->assertInstanceOf('Didww\Item\Country', $countryRelation->getIncluded());
        $this->assertEquals([
            'iso' => 'IT',
            'prefix' => '39',
            'name' => 'Italy',
        ], $countryRelation->getIncluded()->getAttributes());

        $this->stopVCR();
    }
}
