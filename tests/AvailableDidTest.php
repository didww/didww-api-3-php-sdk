<?php

namespace Didww\Tests;

class AvailableDidTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'available_dids.yml';
    }

    public function testAll()
    {
        $availableDidsDocument = \Didww\Item\AvailableDid::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\AvailableDid', $availableDidsDocument->getData());
    }

    public function testFilterByNanpaPrefix()
    {
        $availableDidsDocument = \Didww\Item\AvailableDid::all(['filter' => ['nanpa_prefix.id' => 'eeed293b-f3d8-4ef8-91ef-1b077d174b3b']]);
        $this->assertContainsOnlyInstancesOf('Didww\Item\AvailableDid', $availableDidsDocument->getData());
    }

    public function testFindWithNanpaPrefix()
    {
        $uuid = '0e1c548e-c6b5-43b0-9c12-2e300178e820';
        $availableDidDocument = \Didww\Item\AvailableDid::find($uuid, ['include' => 'nanpa_prefix']);

        $this->assertInstanceOf('Didww\Item\AvailableDid', $availableDidDocument->getData());
        $this->assertEquals('12012213879', $availableDidDocument->getData()->getNumber());

        $nanpaPrefix = $availableDidDocument->getData()->nanpaPrefix()->getIncluded();
        $this->assertInstanceOf('Didww\Item\NanpaPrefix', $nanpaPrefix);
        $this->assertEquals('1e622e21-c740-4d3f-a615-2a7ef4991922', $nanpaPrefix->getId());
        $this->assertEquals([
            'npa' => '201',
            'nxx' => '221',
        ], $nanpaPrefix->getAttributes());
    }

    public function testFind()
    {
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
    }
}
