<?php

namespace Didww\Tests;

class DidTest extends BaseTest
{
    public function testAllWithIncludesAndPagination()
    {
        $this->startVCR('dids.yml');

        $dids = \Didww\Item\Did::all(['include' => 'order', 'page' => ['size' => 10, 'number' => 2]]);
        $this->assertContainsOnlyInstancesOf('Didww\Item\Did', $dids->getData());
        $order = $dids->getData()[0]->order()->getIncluded();
        $this->assertInstanceOf('Didww\Item\Order', $order);
        $didGroup = $dids->getData()[0]->didGroup()->getIncluded();
        $this->assertNull($didGroup);
        $this->assertEquals($dids->getMeta()['total_records'], 13);

        $this->stopVCR();
    }

    public function testApplyTrunkGroup()
    {
        $this->startVCR('dids.yml');

        $voiceInTrunkGroup = new \Didww\Item\VoiceInTrunkGroup();
        $voiceInTrunkGroup->setId('837c5764-a6c3-456f-aa37-71fc8f8ca07b');

        $did = new \Didww\Item\Did();
        $did->setId('9df99644-f1a5-4a3c-99a4-559d758eb96b');
        $did->setVoiceInTrunkGroup($voiceInTrunkGroup);
        $did->save();

        $this->assertEquals($did->toJsonApiArray(), [
            'id' => $did->getId(),
            'type' => 'dids',
            'relationships' => [
                'voice_in_trunk_group' => [
                    'data' => [
                        'type' => 'voice_in_trunk_groups',
                        'id' => $voiceInTrunkGroup->getId(),
                    ],
                ],
                'voice_in_trunk' => [
                    'data' => null,
                ],
            ],
        ]);

        $this->stopVCR();
    }

    public function testApplyInvalidTrunkGroup()
    {
        $this->startVCR('dids.yml');

        $voiceInTrunkGroup = new \Didww\Item\VoiceInTrunkGroup();
        $voiceInTrunkGroup->setId('invalid');

        $did = new \Didww\Item\Did();
        $did->setId('9df99644-f1a5-4a3c-99a4-559d758eb96b');
        $did->setVoiceInTrunkGroup($voiceInTrunkGroup);
        $response = $did->save();
        $this->assertEquals($response->getErrors()->all()[0]->getDetail(), 'voice_in_trunk_group - is invalid');

        $this->stopVCR();
    }

    public function testApplyCapacityPool()
    {
        $this->startVCR('dids.yml');

        $capacityPool = new \Didww\Item\CapacityPool();
        $capacityPool->setId('f288d07c-e2fc-4ae6-9837-b18fb469c324');

        $did = new \Didww\Item\Did();
        $did->setId('9df99644-f1a5-4a3c-99a4-559d758eb96b');
        $did->setCapacityPool($capacityPool);
        $did->save();

        $this->assertEquals($did->toJsonApiArray(), [
            'id' => $did->getId(),
            'type' => 'dids',
            'relationships' => [
                'capacity_pool' => [
                    'data' => [
                        'type' => 'capacity_pools',
                        'id' => $capacityPool->getId(),
                    ],
                ],
            ],
        ]);

        $this->stopVCR();
    }

    public function testApplySharedCapacityGroup()
    {
        $this->startVCR('dids.yml');

        $sharedCapacityGroup = new \Didww\Item\SharedCapacityGroup();
        $sharedCapacityGroup->setId('206881de-7a92-4415-aa32-b05458c79623');

        $did = new \Didww\Item\Did();
        $did->setId('9df99644-f1a5-4a3c-99a4-559d758eb96b');
        $did->setSharedCapacityGroup($sharedCapacityGroup);
        $did->save();

        $this->assertEquals($did->toJsonApiArray(), [
            'id' => $did->getId(),
            'type' => 'dids',
            'relationships' => [
                'shared_capacity_group' => [
                    'data' => [
                        'type' => 'shared_capacity_groups',
                        'id' => $sharedCapacityGroup->getId(),
                    ],
                ],
            ],
        ]);

        $this->stopVCR();
    }

    public function testBooleans()
    {
        $this->startVCR('dids.yml');

        $didDocument = \Didww\Item\Did::find('9df99644-f1a5-4a3c-99a4-559d758eb96b');
        $did = $didDocument->getData();
        $this->assertEquals($did->getBillingCyclesCount(), null);
        $this->assertEquals($did->getTerminated(), false);
        $this->assertEquals($did->getNumber(), '16091609123456797');
        $this->assertEquals($did->getBlocked(), false);
        $this->assertEquals($did->getAwaitingRegistration(), false);
        $this->assertEquals($did->getDescription(), null);

        $did->setBillingCyclesCount(0);
        $did->setTerminated(true);
        $did->setDescription('something');
        $didDocument = $did->save();

        $this->assertEquals($did->toJsonApiArray(), [
            'id' => $did->getId(),
            'type' => 'dids',
            'attributes' => [
                'capacity_limit' => $did->getCapacityLimit(),
                'description' => $did->getDescription(),
                'terminated' => true,
                'billing_cycles_count' => 0,
                'dedicated_channels_count' => $did->getDedicatedChannelsCount(),
            ],
        ]);

        $did = $didDocument->getData();

        $this->assertEquals($did->getBillingCyclesCount(), 0);
        $this->assertEquals($did->getTerminated(), true);

        $this->stopVCR();
    }
}
