<?php

namespace Didww\Tests;

class DidReservationTest extends BaseTest
{
    public function testDidReservationSave()
    {
        $this->startVCR('did_reservations.yml');

        $availableDid = new \Didww\Item\AvailableDid();
        $availableDid->setId('857d1462-5f43-4238-b007-ff05f282e41b');

        $attributes = [
            'description' => 'DIDWW',
        ];
        $didReservation = new \Didww\Item\DidReservation($attributes);
        $didReservation->setAvailableDid($availableDid);
        $this->assertEquals($didReservation->toJsonApiArray(), [
            'type' => 'did_reservations',
            'attributes' => [
                'description' => 'DIDWW',
            ],
            'relationships' => [
                'available_did' => [
                    'data' => [
                        'type' => 'available_dids',
                        'id' => $availableDid->getId(),
                    ],
                ],
            ],
        ]);
        $response = $didReservation->save();
        $this->assertInstanceOf('Didww\Item\DidReservation', $response->getData());

        $this->stopVCR();
    }

    public function testAll()
    {
        $this->startVCR('did_reservations.yml');

        $didReservationsDocument = \Didww\Item\DidReservation::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\DidReservation', $didReservationsDocument->getData());

        $this->stopVCR();
    }

    public function testFind()
    {
        $this->startVCR('did_reservations.yml');

        $uuid = 'fd38d3ff-80cf-4e67-a605-609a2884a5c4';
        $didReservationDocument = \Didww\Item\DidReservation::find($uuid, ['include' => 'available_did.did_group.stock_keeping_units']);

        $availableDidRelation = $didReservationDocument->getData()->availableDid();
        $didGroupRelation = $availableDidRelation->getIncluded()->didGroup();
        $stockKeepingUnitsRelation = $didGroupRelation->getIncluded()->stockKeepingUnits();

        $this->assertInstanceOf('Didww\Item\DidReservation', $didReservationDocument->getData());
        $this->assertEquals($didReservationDocument->getData()->getAttributes(), [
            'description' => 'DIDWW',
            'expire_at' => '2018-12-28T16:22:00.417Z',
            'created_at' => '2018-12-28T16:12:00.440Z',
        ]);
        $this->assertInstanceOf('Didww\Item\DidGroup', $didGroupRelation->getIncluded());
        $this->assertEquals($didGroupRelation->getIncluded()->getAttributes(), [
            'prefix' => '949',
            'features' => ['voice'],
            'is_metered' => false,
            'area_name' => 'Saddleback Valley',
            'allow_additional_channels' => true,
        ]);
        $this->assertContainsOnlyInstancesOf('Didww\Item\StockKeepingUnit', $stockKeepingUnitsRelation->getIncluded()->all());
        $this->assertCount(2, $stockKeepingUnitsRelation->getIncluded()->all());
        $this->assertEquals($stockKeepingUnitsRelation->getIncluded()->all()[1]->getAttributes(), [
            'setup_price' => '0.0',
            'monthly_price' => '0.19',
            'channels_included_count' => 2,
        ]);

        $this->stopVCR();
    }

    public function testDeleteDidReservation()
    {
        $this->startVCR('did_reservations.yml');

        $uuid = '8a18a19f-b082-42f3-acca-99ea402a4e5d';
        $didReservation = \Didww\Item\DidReservation::build($uuid);
        $didReservationDocument = $didReservation->delete();
        $this->assertFalse($didReservationDocument->hasErrors());

        $this->stopVCR();
    }
}
