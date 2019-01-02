<?php

namespace Didww\Tests;

class CapacityPoolTest extends BaseTest
{
    public function testAll()
    {
        $this->startVCR('capacity_pools.yml');

        $capacityPoolsDocument = \Didww\Item\CapacityPool::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\CapacityPool', $capacityPoolsDocument->getData());

        $this->stopVCR();
    }

    public function testFind()
    {
        $this->startVCR('capacity_pools.yml');

        $uuid = 'f288d07c-e2fc-4ae6-9837-b18fb469c324';
        $capacityPoolDocument = \Didww\Item\CapacityPool::find($uuid, ['include' => 'countries,shared_capacity_groups,qty_based_pricings']);
        $countriesRelation = $capacityPoolDocument->getData()->countries();
        $sharedCapacityGroupsRelation = $capacityPoolDocument->getData()->sharedCapacityGroups();
        $qtyBasedPricingsRelation = $capacityPoolDocument->getData()->qtyBasedPricings();

        $this->assertInstanceOf('Didww\Item\CapacityPool', $capacityPoolDocument->getData());
        $this->assertEquals($capacityPoolDocument->getData()->getAttributes(), [
            'name' => 'Standard',
            'renew_date' => '2019-01-21',
            'total_channels_count' => 34,
            'assigned_channels_count' => 24,
            'minimum_limit' => 0,
            'minimum_qty_per_order' => 1,
            'setup_price' => '0.0',
            'monthly_price' => '15.0',
            'metered_rate' => '1.0',
        ]);

        $this->assertContainsOnlyInstancesOf('Didww\Item\Country', $countriesRelation->getIncluded()->all());
        $this->assertEquals(array_map(
            function ($value) {
                return $value->getAttributes();
            },
            //show only first 2 countries
            array_slice($countriesRelation->getIncluded()->all(), 0, 2)
        ), [
            [
                'name' => 'Peru',
                'iso' => 'PE',
                'prefix' => '51',
            ],
            [
                'name' => 'Iceland',
                'iso' => 'IS',
                'prefix' => '354',
            ],
        ]);

        $this->assertContainsOnlyInstancesOf('Didww\Item\SharedCapacityGroup', $sharedCapacityGroupsRelation->getIncluded()->all());
        $this->assertEquals(array_map(
            function ($value) { return $value->getAttributes(); },
                $sharedCapacityGroupsRelation->getIncluded()->all()
            ), [
                [
                    'name' => 'test',
                    'shared_channels_count' => 5,
                    'metered_channels_count' => 5,
                    'created_at' => '2018-06-19T12:26:48.938Z',
                ],
                [
                    'name' => 'didww',
                    'shared_channels_count' => 19,
                    'metered_channels_count' => 0,
                    'created_at' => '2018-06-19T11:41:21.644Z',
                ],
                [
                    'name' => 'Pay Per Minute',
                    'shared_channels_count' => 0,
                    'metered_channels_count' => 100,
                    'created_at' => '2018-06-19T11:41:21.644Z',
                ],
            ]);

        $this->assertContainsOnlyInstancesOf('Didww\Item\QtyBasedPricing', $qtyBasedPricingsRelation->getIncluded()->all());
        $this->assertEquals(array_map(
            function ($value) { return $value->getAttributes(); },
                $qtyBasedPricingsRelation->getIncluded()->all()
            ), [
                [
                    'qty' => 30,
                    'monthly_price' => '10.0',
                    'setup_price' => '10.0',
                ],
                [
                    'qty' => 300,
                    'monthly_price' => '8.0',
                    'setup_price' => '8.0',
                ],
                [
                    'qty' => 990,
                    'monthly_price' => '7.0',
                    'setup_price' => '7.0',
                ],
            ]);

        $this->stopVCR();
    }

    public function testUpdateCapacityPool()
    {
        $this->startVCR('capacity_pools.yml');

        $uuid = 'f288d07c-e2fc-4ae6-9837-b18fb469c324';
        $capacityPool = \Didww\Item\CapacityPool::build($uuid, ['total_channels_count' => 25]);
        $capacityPoolDocument = $capacityPool->save();

        $this->assertInstanceOf('Didww\Item\CapacityPool', $capacityPoolDocument->getData());
        $this->assertEquals($capacityPoolDocument->getData()->getAttributes(), [
            'name' => 'Standard',
            'renew_date' => '2019-01-21',
            'total_channels_count' => 25,
            'assigned_channels_count' => 24,
            'minimum_limit' => 0,
            'minimum_qty_per_order' => 1,
            'setup_price' => '0.0',
            'monthly_price' => '15.0',
            'metered_rate' => '1.0',
        ]);

        $this->stopVCR();
    }
}
