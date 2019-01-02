<?php

namespace Didww\Tests;

class TrunkGroupTest extends BaseTest
{
    public function testAllWithIncludesAndPagination()
    {
        $this->startVCR('trunk_groups.yml');
        $trunkGroupsDocument = \Didww\Item\TrunkGroup::all(['include' => 'trunks', 'page' => ['size' => 5, 'number' => 1]]);
        $trunkGroups = $trunkGroupsDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\TrunkGroup', $trunkGroups);
        $trunks = $trunkGroups[0]->trunks()->getIncluded();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Trunk', $trunks);
        $this->assertEquals($trunkGroupsDocument->getMeta()['total_records'], 1);
        $this->stopVCR();
    }

    public function testCreateTrunkGroup()
    {
        $this->startVCR('trunk_groups.yml');
        $attributes = [
          'name' => 'trunk group sample with 2 trunks',
          'capacity_limit' => 1000,
       ];

        $trunks = new \Swis\JsonApi\Client\Collection([
         \Didww\Item\Trunk::build('7c15bca2-7f17-46fb-9486-7e2a17158c7e'),
         \Didww\Item\Trunk::build('b07a4cab-48c6-4b3a-9670-11b90b81bdef'),
       ]);

        $trunkGroup = new \Didww\Item\TrunkGroup($attributes);
        $trunkGroup->setTrunks($trunks);
        $this->assertArraySubset($attributes, $trunkGroup->getAttributes());
        $trunkGroupDocument = $trunkGroup->save(['include' => 'trunks']);
        $trunkGroup = $trunkGroupDocument->getData();
        $this->assertArraySubset($attributes, $trunkGroup->getAttributes());
        $this->assertInstanceOf('Didww\Item\TrunkGroup', $trunkGroup);

        $this->stopVCR();
    }

    public function testUpdateTrunkGroup()
    {
        $this->startVCR('trunk_groups.yml');
        $attributes = [
          'name' => 'trunk group sample updated with 2 trunks',
          'capacity_limit' => 500,
       ];

        $trunkGroup = \Didww\Item\TrunkGroup::build('b2319703-ce6c-480d-bb53-614e7abcfc96');
        $trunkGroup->fill($attributes);
        $trunkGroupDocument = $trunkGroup->save();
        $trunkGroup = $trunkGroupDocument->getData();
        $this->assertInstanceOf('Didww\Item\TrunkGroup', $trunkGroup);
        $this->assertArraySubset($attributes, $trunkGroup->getAttributes());
        $this->stopVCR();
    }

    public function testDeleteTrunkGroup()
    {
        $this->startVCR('trunk_groups.yml');

        $trunkGroup = \Didww\Item\TrunkGroup::build('b2319703-ce6c-480d-bb53-614e7abcfc96');

        $trunkGroupDocument = $trunkGroup->delete();

        $this->assertFalse($trunkGroupDocument->hasErrors());
        $this->stopVCR();
    }
}
