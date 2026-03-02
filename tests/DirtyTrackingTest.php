<?php

namespace Didww\Tests;

class DirtyTrackingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Scenario 1: build(id), set one attribute, update -> only that attribute is sent.
     */
    public function testBuildSetOneAttributePatchContainsOnlyThatAttribute()
    {
        $did = \Didww\Item\Did::build('test-uuid');
        $did->setDescription('new description');

        $patch = $did->toJsonApiPatchArray();

        $this->assertEquals([
            'type' => 'dids',
            'id' => 'test-uuid',
            'attributes' => [
                'description' => 'new description',
            ],
        ], $patch);
    }

    /**
     * Scenario 2: Set attribute to null, update -> explicit null is sent.
     */
    public function testSetAttributeToNullSendsExplicitNull()
    {
        $did = \Didww\Item\Did::build('test-uuid');
        $did->setBillingCyclesCount(null);

        $patch = $did->toJsonApiPatchArray();

        $this->assertEquals([
            'type' => 'dids',
            'id' => 'test-uuid',
            'attributes' => [
                'billing_cycles_count' => null,
            ],
        ], $patch);
    }

    /**
     * Scenario 3: Load object from API (simulated), set one field, update -> only changed field is sent.
     */
    public function testLoadedObjectOnlyDirtyFieldSent()
    {
        $did = new \Didww\Item\Did([
            'description' => 'original',
            'capacity_limit' => 10,
            'terminated' => false,
            'billing_cycles_count' => null,
            'dedicated_channels_count' => 1,
        ]);
        $did->setId('test-uuid');
        $did->syncPersistedState();

        $did->setDescription('updated');

        $patch = $did->toJsonApiPatchArray();

        $this->assertEquals([
            'type' => 'dids',
            'id' => 'test-uuid',
            'attributes' => [
                'description' => 'updated',
            ],
        ], $patch);
    }

    /**
     * Scenario 4: Set voice_in_trunk, ensure voice_in_trunk_group is explicitly null in PATCH.
     */
    public function testSetVoiceInTrunkClearsVoiceInTrunkGroup()
    {
        $did = \Didww\Item\Did::build('test-uuid');

        $trunk = new \Didww\Item\VoiceInTrunk();
        $trunk->setId('trunk-uuid');
        $did->setVoiceInTrunk($trunk);

        $patch = $did->toJsonApiPatchArray();

        $this->assertEquals([
            'type' => 'dids',
            'id' => 'test-uuid',
            'relationships' => [
                'voice_in_trunk' => [
                    'data' => [
                        'type' => 'voice_in_trunks',
                        'id' => 'trunk-uuid',
                    ],
                ],
                'voice_in_trunk_group' => [
                    'data' => null,
                ],
            ],
        ], $patch);
    }

    /**
     * Scenario 5: Set voice_in_trunk_group, ensure voice_in_trunk is explicitly null in PATCH.
     */
    public function testSetVoiceInTrunkGroupClearsVoiceInTrunk()
    {
        $did = \Didww\Item\Did::build('test-uuid');

        $trunkGroup = new \Didww\Item\VoiceInTrunkGroup();
        $trunkGroup->setId('trunk-group-uuid');
        $did->setVoiceInTrunkGroup($trunkGroup);

        $patch = $did->toJsonApiPatchArray();

        $this->assertEquals([
            'type' => 'dids',
            'id' => 'test-uuid',
            'relationships' => [
                'voice_in_trunk_group' => [
                    'data' => [
                        'type' => 'voice_in_trunk_groups',
                        'id' => 'trunk-group-uuid',
                    ],
                ],
                'voice_in_trunk' => [
                    'data' => null,
                ],
            ],
        ], $patch);
    }

    /**
     * After syncPersistedState, no dirty fields should be detected.
     */
    public function testSyncPersistedStateClearsDirtyState()
    {
        $did = new \Didww\Item\Did([
            'description' => 'test',
            'capacity_limit' => 5,
            'terminated' => false,
            'billing_cycles_count' => null,
            'dedicated_channels_count' => 1,
        ]);
        $did->setId('test-uuid');
        $did->syncPersistedState();

        $patch = $did->toJsonApiPatchArray();

        $this->assertEquals([
            'type' => 'dids',
            'id' => 'test-uuid',
        ], $patch);
    }

    /**
     * Test that multiple attribute changes are all tracked.
     */
    public function testMultipleDirtyAttributes()
    {
        $did = \Didww\Item\Did::build('test-uuid');
        $did->setDescription('new');
        $did->setCapacityLimit(20);
        $did->setTerminated(true);

        $patch = $did->toJsonApiPatchArray();

        $this->assertEquals([
            'type' => 'dids',
            'id' => 'test-uuid',
            'attributes' => [
                'description' => 'new',
                'capacity_limit' => 20,
                'terminated' => true,
            ],
        ], $patch);
    }

    /**
     * Test loaded object with only relationship change sends only relationship.
     */
    public function testLoadedObjectRelationshipChangeOnly()
    {
        $did = new \Didww\Item\Did([
            'description' => 'original',
            'capacity_limit' => 10,
            'terminated' => false,
            'billing_cycles_count' => null,
            'dedicated_channels_count' => 1,
        ]);
        $did->setId('test-uuid');
        $did->syncPersistedState();

        $trunk = new \Didww\Item\VoiceInTrunk();
        $trunk->setId('trunk-uuid');
        $did->setVoiceInTrunk($trunk);

        $patch = $did->toJsonApiPatchArray();

        $this->assertEquals([
            'type' => 'dids',
            'id' => 'test-uuid',
            'relationships' => [
                'voice_in_trunk' => [
                    'data' => [
                        'type' => 'voice_in_trunks',
                        'id' => 'trunk-uuid',
                    ],
                ],
                'voice_in_trunk_group' => [
                    'data' => null,
                ],
            ],
        ], $patch);
    }

    /**
     * Test that toJsonApiArray still returns full payload (unchanged for CREATE).
     */
    public function testCreateBehaviorUnchanged()
    {
        $did = new \Didww\Item\Did([
            'description' => 'new did',
            'capacity_limit' => 10,
            'terminated' => false,
        ]);

        $array = $did->toJsonApiArray();

        $this->assertEquals([
            'type' => 'dids',
            'attributes' => [
                'description' => 'new did',
                'capacity_limit' => 10,
                'terminated' => false,
            ],
        ], $array);
    }

    /**
     * Test persisted attribute changing from value to null is tracked.
     */
    public function testChangeAttributeFromValueToNull()
    {
        $did = new \Didww\Item\Did([
            'description' => 'original',
            'capacity_limit' => 10,
            'terminated' => false,
            'billing_cycles_count' => 5,
            'dedicated_channels_count' => 1,
        ]);
        $did->setId('test-uuid');
        $did->syncPersistedState();

        $did->setBillingCyclesCount(null);

        $patch = $did->toJsonApiPatchArray();

        $this->assertEquals([
            'type' => 'dids',
            'id' => 'test-uuid',
            'attributes' => [
                'billing_cycles_count' => null,
            ],
        ], $patch);
    }

    /**
     * Test build without attributes + set attribute only sends that attribute (Identity).
     */
    public function testBuildIdentitySetOneAttribute()
    {
        $identity = \Didww\Item\Identity::build('test-uuid');
        $identity->setFirstName('John');

        $patch = $identity->toJsonApiPatchArray();

        $this->assertEquals([
            'type' => 'identities',
            'id' => 'test-uuid',
            'attributes' => [
                'first_name' => 'John',
            ],
        ], $patch);
    }

    /**
     * Test clearing an existing relationship after load.
     */
    public function testClearExistingRelationship()
    {
        $did = new \Didww\Item\Did([
            'description' => 'original',
            'capacity_limit' => 10,
            'terminated' => false,
            'billing_cycles_count' => null,
            'dedicated_channels_count' => 1,
        ]);
        $did->setId('test-uuid');

        $trunk = new \Didww\Item\VoiceInTrunk();
        $trunk->setId('old-trunk-uuid');
        $did->voiceInTrunk()->associate($trunk);

        $did->syncPersistedState();

        $did->voiceInTrunk()->dissociate();

        $patch = $did->toJsonApiPatchArray();

        $this->assertEquals([
            'type' => 'dids',
            'id' => 'test-uuid',
            'relationships' => [
                'voice_in_trunk' => [
                    'data' => null,
                ],
            ],
        ], $patch);
    }

    /**
     * Test that both attribute and relationship changes are tracked together.
     */
    public function testMixedAttributeAndRelationshipChanges()
    {
        $did = new \Didww\Item\Did([
            'description' => 'original',
            'capacity_limit' => 10,
            'terminated' => false,
            'billing_cycles_count' => null,
            'dedicated_channels_count' => 1,
        ]);
        $did->setId('test-uuid');
        $did->syncPersistedState();

        $did->setDescription('changed');
        $trunk = new \Didww\Item\VoiceInTrunk();
        $trunk->setId('new-trunk-uuid');
        $did->setVoiceInTrunk($trunk);

        $patch = $did->toJsonApiPatchArray();

        $this->assertEquals([
            'type' => 'dids',
            'id' => 'test-uuid',
            'attributes' => [
                'description' => 'changed',
            ],
            'relationships' => [
                'voice_in_trunk' => [
                    'data' => [
                        'type' => 'voice_in_trunks',
                        'id' => 'new-trunk-uuid',
                    ],
                ],
                'voice_in_trunk_group' => [
                    'data' => null,
                ],
            ],
        ], $patch);
    }

    /**
     * Test PatchItemDocument serializes using toJsonApiPatchArray.
     */
    public function testPatchItemDocumentSerialization()
    {
        $did = \Didww\Item\Did::build('test-uuid');
        $did->setDescription('only this');

        $document = new \Didww\PatchItemDocument();
        $document->setData($did);

        $json = json_encode($document, JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, true);

        $this->assertEquals([
            'data' => [
                'type' => 'dids',
                'id' => 'test-uuid',
                'attributes' => [
                    'description' => 'only this',
                ],
            ],
        ], $decoded);
    }

    /**
     * Test that re-setting an attribute to its persisted value makes it non-dirty.
     */
    public function testResetToPersistedValueIsNotDirty()
    {
        $did = new \Didww\Item\Did([
            'description' => 'original',
            'capacity_limit' => 10,
            'terminated' => false,
            'billing_cycles_count' => null,
            'dedicated_channels_count' => 1,
        ]);
        $did->setId('test-uuid');
        $did->syncPersistedState();

        $did->setDescription('original');

        $patch = $did->toJsonApiPatchArray();

        $this->assertEquals([
            'type' => 'dids',
            'id' => 'test-uuid',
        ], $patch);
    }

    /**
     * Test that switching trunk from group to individual sends both changes.
     */
    public function testSwitchFromTrunkGroupToTrunkAfterLoad()
    {
        $did = new \Didww\Item\Did([
            'description' => 'test',
            'capacity_limit' => 10,
            'terminated' => false,
            'billing_cycles_count' => null,
            'dedicated_channels_count' => 1,
        ]);
        $did->setId('test-uuid');

        $oldGroup = new \Didww\Item\VoiceInTrunkGroup();
        $oldGroup->setId('old-group-uuid');
        $did->voiceInTrunkGroup()->associate($oldGroup);

        $did->syncPersistedState();

        $newTrunk = new \Didww\Item\VoiceInTrunk();
        $newTrunk->setId('new-trunk-uuid');
        $did->setVoiceInTrunk($newTrunk);

        $patch = $did->toJsonApiPatchArray();

        $this->assertEquals([
            'type' => 'dids',
            'id' => 'test-uuid',
            'relationships' => [
                'voice_in_trunk' => [
                    'data' => [
                        'type' => 'voice_in_trunks',
                        'id' => 'new-trunk-uuid',
                    ],
                ],
                'voice_in_trunk_group' => [
                    'data' => null,
                ],
            ],
        ], $patch);
    }
}
