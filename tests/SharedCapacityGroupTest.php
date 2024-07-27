<?php

namespace Didww\Tests;

class SharedCapacityGroupTest extends BaseTest
{
    public function testAll()
    {
        $this->startVCR('shared_capacity_groups.yml');

        $sharedCapacityGroupsDocument = \Didww\Item\SharedCapacityGroup::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\SharedCapacityGroup', $sharedCapacityGroupsDocument->getData());

        $this->stopVCR();
    }

    public function testFind()
    {
        $this->startVCR('shared_capacity_groups.yml');

        $uuid = '89f987e2-0862-4bf4-a3f4-cdc89af0d875';
        $sharedCapacityGroupDocument = \Didww\Item\SharedCapacityGroup::find($uuid, ['include' => 'dids,capacity_pool']);
        $didsRelation = $sharedCapacityGroupDocument->getData()->dids();
        $capacityPoolRelation = $sharedCapacityGroupDocument->getData()->capacityPool();

        $this->assertInstanceOf('Didww\Item\SharedCapacityGroup', $sharedCapacityGroupDocument->getData());
        $this->assertEquals($sharedCapacityGroupDocument->getData()->getAttributes(), [
            'name' => 'didww',
            'shared_channels_count' => 19,
            'metered_channels_count' => 0,
            'created_at' => '2018-06-19T11:41:21.644Z',
        ]);

        $this->assertContainsOnlyInstancesOf('Didww\Item\Did', $didsRelation->getIncluded()->all());
        $this->assertEquals(array_map(
            function ($value) {
                return $value->getAttributes();
            },
            array_slice($didsRelation->getIncluded()->all(), 0, 2)
        ), [
            [
                'blocked' => false,
                'capacity_limit' => 2,
                'description' => 'something',
                'terminated' => false,
                'awaiting_registration' => false,
                'number' => '18082068798',
                'channels_included_count' => 2,
                'billing_cycles_count' => null,
                'dedicated_channels_count' => 0,
                'created_at' => '2018-12-27T16:44:05.634Z',
                'expires_at' => '2019-01-27T16:44:06.207Z',
            ],
            [
                'blocked' => true,
                'capacity_limit' => 2,
                'description' => 'something',
                'terminated' => false,
                'awaiting_registration' => false,
                'number' => '18082068794',
                'channels_included_count' => 2,
                'billing_cycles_count' => null,
                'dedicated_channels_count' => 0,
                'created_at' => '2018-12-27T16:45:15.997Z',
                'expires_at' => '2019-01-27T16:45:15.957Z',
            ],
        ]);

        $this->assertInstanceOf('Didww\Item\CapacityPool', $capacityPoolRelation->getIncluded());
        $this->assertEquals($capacityPoolRelation->getIncluded()->getAttributes(), [
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

    public function testUpdateSharedCapacityGroup()
    {
        $this->startVCR('shared_capacity_groups.yml');

        $uuid = '89f987e2-0862-4bf4-a3f4-cdc89af0d875';
        $sharedCapacityGroup = \Didww\Item\SharedCapacityGroup::build($uuid, ['name' => 'didww1', 'shared_channels_count' => 10, 'metered_channels_count' => 2]);
        $sharedCapacityGroupDocument = $sharedCapacityGroup->save();

        $this->assertInstanceOf('Didww\Item\SharedCapacityGroup', $sharedCapacityGroupDocument->getData());
        $this->assertEquals($sharedCapacityGroupDocument->getData()->getAttributes(), [
            'name' => 'didww1',
            'shared_channels_count' => 10,
            'metered_channels_count' => 2,
            'created_at' => '2018-06-19T11:41:21.644Z',
        ]);

        $this->stopVCR();
    }

    public function testUpdateSharedCapacityGroupWithDidsRelationship()
    {
        $this->startVCR('shared_capacity_groups.yml');

        $uuid = '89f987e2-0862-4bf4-a3f4-cdc89af0d875';
        $sharedCapacityGroup = \Didww\Item\SharedCapacityGroup::build($uuid);
        $dids = new \Swis\JsonApi\Client\Collection([
            \Didww\Item\Did::build('9df99644-f1a5-4a3c-99a4-559d758eb96b'),
        ]);
        $sharedCapacityGroup->setDids($dids);
        $sharedCapacityGroupDocument = $sharedCapacityGroup->save(['include' => 'dids']);
        $didsRelation = $sharedCapacityGroupDocument->getData()->dids();

        $this->assertInstanceOf('Didww\Item\SharedCapacityGroup', $sharedCapacityGroupDocument->getData());
        $this->assertEquals($sharedCapacityGroupDocument->getData()->getAttributes(), [
            'name' => 'didww1',
            'shared_channels_count' => 10,
            'metered_channels_count' => 2,
            'created_at' => '2018-06-19T11:41:21.644Z',
        ]);

        $this->assertContainsOnlyInstancesOf('Didww\Item\Did', $didsRelation->getIncluded()->all());
        $this->assertEquals(array_map(
            function ($value) {
                return $value->getAttributes();
            },
            $didsRelation->getIncluded()->all()
        ), [
            [
                'blocked' => false,
                'capacity_limit' => 2,
                'description' => 'something',
                'terminated' => false,
                'awaiting_registration' => false,
                'number' => '16091609123456797',
                'channels_included_count' => 0,
                'billing_cycles_count' => null,
                'dedicated_channels_count' => 0,
                'created_at' => '2018-12-27T09:59:55.015Z',
                'expires_at' => '2019-01-27T10:00:04.755Z',
            ],
        ]);

        $this->stopVCR();
    }

    public function testCreateSharedCapacityGroup()
    {
        $this->startVCR('shared_capacity_groups.yml');

        $attributes = [
            'name' => 'php-sdk',
            'shared_channels_count' => 5,
            'metered_channels_count' => 0,
        ];
        $sharedCapacityGroup = new \Didww\Item\SharedCapacityGroup($attributes);
        $capacityPool = \Didww\Item\CapacityPool::build('f288d07c-e2fc-4ae6-9837-b18fb469c324');
        $sharedCapacityGroup->setCapacityPool($capacityPool);
        $sharedCapacityGroupDocument = $sharedCapacityGroup->save();

        $this->assertInstanceOf('Didww\Item\SharedCapacityGroup', $sharedCapacityGroupDocument->getData());
        $this->assertEquals($sharedCapacityGroupDocument->getData()->getId(), '3688a9c3-354f-4e16-b458-1d2df9f02547');
        $this->assertEquals($sharedCapacityGroupDocument->getData()->getAttributes(), [
            'name' => 'php-sdk',
            'shared_channels_count' => 5,
            'metered_channels_count' => 0,
            'created_at' => '2019-01-02T09:41:26.083Z',
        ]);

        $this->stopVCR();
    }

    public function testDeleteSharedCapacityGroup()
    {
        $this->startVCR('shared_capacity_groups.yml');

        $uuid = '3688a9c3-354f-4e16-b458-1d2df9f02547';
        $sharedCapacityGroup = \Didww\Item\SharedCapacityGroup::build($uuid);
        $sharedCapacityGroupDocument = $sharedCapacityGroup->delete();
        $this->assertFalse($sharedCapacityGroupDocument->hasErrors());

        $this->stopVCR();
    }
}
