<?php

namespace Didww\Tests;

class DidGroupTest extends BaseTest
{
    public function testAll()
    {
        $this->startVCR('did_groups.yml');

        $didGroupDocument = \Didww\Item\DidGroup::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\DidGroup', $didGroupDocument->getData());

        $this->stopVCR();
    }

    public function testFind()
    {
        $this->startVCR('did_groups.yml');

        $uuid = '2187c36d-28fb-436f-8861-5a0f5b5a3ee1';
        $didGroupDocument = \Didww\Item\DidGroup::find($uuid, ['include' => 'country,region,city,did_group_type,stock_keeping_units']);
        $countryRelation = $didGroupDocument->getData()->country();
        $regionRelation = $didGroupDocument->getData()->region();
        $cityRelation = $didGroupDocument->getData()->city();
        $didGroupTypeRelation = $didGroupDocument->getData()->didGroupType();
        $stockKeepingUnitsRelation = $didGroupDocument->getData()->stockKeepingUnits();

        $this->assertInstanceOf('Didww\Item\DidGroup', $didGroupDocument->getData());
        $this->assertEquals($didGroupDocument->getData()->getAttributes(), [
            'prefix' => '241',
            'local_prefix' => '',
            'features' => ['voice'],
            'is_metered' => false,
            'area_name' => 'Aachen',
            'allow_additional_channels' => true,
        ]);

        $this->assertInstanceOf('Didww\Item\Country', $countryRelation->getIncluded());
        $this->assertEquals($countryRelation->getIncluded()->getAttributes(), [
            'name' => 'Germany',
            'iso' => 'DE',
            'prefix' => '49',
        ]);

        $this->assertNull($regionRelation->getIncluded());

        $this->assertInstanceOf('Didww\Item\City', $cityRelation->getIncluded());
        $this->assertEquals($cityRelation->getIncluded()->getAttributes(), [
            'name' => 'Aachen',
        ]);

        $this->assertInstanceOf('Didww\Item\DidGroupType', $didGroupTypeRelation->getIncluded());
        $this->assertEquals($didGroupTypeRelation->getIncluded()->getAttributes(), [
            'name' => 'Local',
        ]);

        $stockKeepingUnits = $stockKeepingUnitsRelation->getIncluded()->all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\StockKeepingUnit', $stockKeepingUnits);

        $this->assertEquals(0.4, $stockKeepingUnits[0]->getSetupPrice());
        $this->assertEquals(0.8, $stockKeepingUnits[0]->getMonthlyPrice());
        $this->assertEquals(0, $stockKeepingUnits[0]->getChannelsIncludedCount());

        $this->assertEquals(1.0, $stockKeepingUnits[1]->getSetupPrice());
        $this->assertEquals(4.8, $stockKeepingUnits[1]->getMonthlyPrice());
        $this->assertEquals(2, $stockKeepingUnits[1]->getChannelsIncludedCount());

        $this->stopVCR();
    }
}
