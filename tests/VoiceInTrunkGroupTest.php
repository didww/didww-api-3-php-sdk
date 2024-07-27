<?php

namespace Didww\Tests;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

class VoiceInTrunkGroupTest extends BaseTest
{
    use ArraySubsetAsserts;

    public function testAllWithIncludesAndPagination()
    {
        $this->startVCR('voice_in_trunk_groups.yml');
        $voiceInTrunkGroupsDocument = \Didww\Item\VoiceInTrunkGroup::all(['include' => 'trunks', 'page' => ['size' => 5, 'number' => 1]]);
        $voiceInTrunkGroups = $voiceInTrunkGroupsDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\VoiceInTrunkGroup', $voiceInTrunkGroups);
        $voiceInTrunks = $voiceInTrunkGroups[0]->voiceInTrunks()->getIncluded();
        $this->assertContainsOnlyInstancesOf('Didww\Item\VoiceInTrunk', $voiceInTrunks);
        $this->assertEquals($voiceInTrunkGroupsDocument->getMeta()['total_records'], 1);
        $this->stopVCR();
    }

    public function testCreateTrunkGroup()
    {
        $this->startVCR('voice_in_trunk_groups.yml');
        $attributes = [
            'name' => 'trunk group sample with 2 trunks',
            'capacity_limit' => 1000,
        ];

        $voiceInTrunks = new \Swis\JsonApi\Client\Collection([
            \Didww\Item\VoiceInTrunk::build('7c15bca2-7f17-46fb-9486-7e2a17158c7e'),
            \Didww\Item\VoiceInTrunk::build('b07a4cab-48c6-4b3a-9670-11b90b81bdef'),
        ]);

        $voiceInTrunkGroup = new \Didww\Item\VoiceInTrunkGroup($attributes);
        $voiceInTrunkGroup->setVoiceInTrunks($voiceInTrunks);
        $this->assertArraySubset($attributes, $voiceInTrunkGroup->getAttributes());
        $voiceInTrunkGroupDocument = $voiceInTrunkGroup->save(['include' => 'trunks']);
        $voiceInTrunkGroup = $voiceInTrunkGroupDocument->getData();
        $this->assertArraySubset($attributes, $voiceInTrunkGroup->getAttributes());
        $this->assertInstanceOf('Didww\Item\VoiceInTrunkGroup', $voiceInTrunkGroup);

        $this->stopVCR();
    }

    public function testUpdateTrunkGroup()
    {
        $this->startVCR('voice_in_trunk_groups.yml');
        $attributes = [
            'name' => 'trunk group sample updated with 2 trunks',
            'capacity_limit' => 500,
        ];

        $voiceInTrunkGroup = \Didww\Item\VoiceInTrunkGroup::build('b2319703-ce6c-480d-bb53-614e7abcfc96');
        $voiceInTrunkGroup->fill($attributes);
        $voiceInTrunkGroupDocument = $voiceInTrunkGroup->save();
        $voiceInTrunkGroup = $voiceInTrunkGroupDocument->getData();
        $this->assertInstanceOf('Didww\Item\VoiceInTrunkGroup', $voiceInTrunkGroup);
        $this->assertArraySubset($attributes, $voiceInTrunkGroup->getAttributes());
        $this->stopVCR();
    }

    public function testDeleteTrunkGroup()
    {
        $this->startVCR('voice_in_trunk_groups.yml');

        $voiceInTrunkGroup = \Didww\Item\VoiceInTrunkGroup::build('b2319703-ce6c-480d-bb53-614e7abcfc96');

        $voiceInTrunkGroupDocument = $voiceInTrunkGroup->delete();

        $this->assertFalse($voiceInTrunkGroupDocument->hasErrors());
        $this->stopVCR();
    }
}
