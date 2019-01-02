<?php

namespace Didww\Tests;

class AvailableDidTest extends BaseTest
{
    public function testAll()
    {
        $this->startVCR('available_dids.yml');

        $availableDidsDocument = \Didww\Item\AvailableDid::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\AvailableDid', $availableDidsDocument->getData());

        $this->stopVCR();
    }

    public function testFind()
    {
        $this->startVCR('available_dids.yml');

        $uuid = '0b76223b-9625-412f-b0f3-330551473e7e';
        $availableDidDocument = \Didww\Item\AvailableDid::find($uuid, ['include' => 'did_group.stock_keeping_units']);

        $didGroupRelation = $availableDidDocument->getData()->didGroup();

        $stockKeepingUnitsRelation = $didGroupRelation->getIncluded()->stockKeepingUnits();

        $this->assertInstanceOf('Didww\Item\AvailableDid', $availableDidDocument->getData());
        $this->assertEquals($availableDidDocument->getData()->getAttributes(), [
            'number' => '16169886810',
        ]);
        $this->assertInstanceOf('Didww\Item\DidGroup', $didGroupRelation->getIncluded());
        $this->assertEquals($didGroupRelation->getIncluded()->getAttributes(), [
            'prefix' => '616',
            'local_prefix' => '',
            'features' => ['voice'],
            'is_metered' => false,
            'area_name' => 'Grand Rapids',
            'allow_additional_channels' => true,
        ]);
        $this->assertContainsOnlyInstancesOf('Didww\Item\StockKeepingUnit', $stockKeepingUnitsRelation->getIncluded()->all());
        $this->assertCount(2, $stockKeepingUnitsRelation->getIncluded()->all());
        $this->assertEquals($stockKeepingUnitsRelation->getIncluded()->all()[0]->getAttributes(), [
            'setup_price' => '0.0',
            'monthly_price' => '0.09',
            'channels_included_count' => 0,
        ]);

        $this->assertEquals($stockKeepingUnitsRelation->getIncluded()->all()[1]->getAttributes(), [
            'setup_price' => '0.0',
            'monthly_price' => '0.19',
            'channels_included_count' => 2,
        ]);

        $this->stopVCR();
    }
}
