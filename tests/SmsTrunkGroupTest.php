<?php

namespace Didww\Tests;

use Didww\Item\SmsTrunk;
use Didww\Item\SmsTrunkGroup;
use Swis\JsonApi\Client\Collection;

class SmsTrunkGroupTest extends CassetteTest
{
    private const GROUP_ID = '2b3c4d5e-0001-4b6c-9d7e-8f9a0b1c2d01';
    private const HTTP_IN_ID = '1a2b3c4d-0002-4a5b-8c6d-7e8f9a0b1c02';
    private const SMPP_SMSC_ID = '1a2b3c4d-0005-4a5b-8c6d-7e8f9a0b1c05';

    protected function getCassetteName(): string
    {
        return 'sms_trunk_groups.yml';
    }

    public function testAll()
    {
        $groups = SmsTrunkGroup::all()->getData();
        $this->assertCount(2, $groups);
        $this->assertContainsOnlyInstancesOf(SmsTrunkGroup::class, $groups);

        $this->assertSame(self::GROUP_ID, $groups[0]->getId());
        $this->assertSame('Inbound SMS', $groups[0]->getName());
        $this->assertNull($groups[0]->getExternalReferenceId());
        $this->assertEquals(new \DateTime('2026-10-01T10:00:00Z'), $groups[0]->getCreatedAt());
        $this->assertEquals(new \DateTime('2026-10-01T10:00:00Z'), $groups[0]->getUpdatedAt());
        $this->assertSame(2, $groups[0]->getSmsTrunksCount());
        $this->assertSame('Outbound SMS', $groups[1]->getName());
        $this->assertSame(0, $groups[1]->getSmsTrunksCount());
    }

    public function testFindWithIncludedSmsTrunks()
    {
        $group = SmsTrunkGroup::find(self::GROUP_ID, ['include' => 'sms_trunks'])->getData();
        $trunks = $group->smsTrunks()->getIncluded();

        $this->assertCount(2, $trunks);
        $this->assertSame(self::HTTP_IN_ID, $trunks[0]->getId());
        $this->assertInstanceOf(\Didww\Item\SmsTrunkConfiguration\HttpIn::class, $trunks[0]->getConfiguration());
        $this->assertSame(self::SMPP_SMSC_ID, $trunks[1]->getId());
        $this->assertInstanceOf(\Didww\Item\SmsTrunkConfiguration\SmppSmsc::class, $trunks[1]->getConfiguration());
        $this->assertSame(2, $group->getSmsTrunksCount());
        $this->assertSame(['type' => 'sms_trunk_groups', 'id' => self::GROUP_ID], $group->toJsonApiPatchArray());
    }

    public function testFindNotFound()
    {
        $document = SmsTrunkGroup::find('2b3c4d5e-0009-4b6c-9d7e-8f9a0b1c2d09');
        $this->assertTrue($document->hasErrors());
        $this->assertSame(404, $document->getResponse()->getStatusCode());
    }

    public function testCreate()
    {
        $group = new SmsTrunkGroup();
        $group->setName('Inbound SMS');
        $group->setSmsTrunks(new Collection([SmsTrunk::build(self::HTTP_IN_ID)]));
        $document = $group->save();

        $created = $document->getData();
        $this->assertInstanceOf(SmsTrunkGroup::class, $created);
        $this->assertSame(self::GROUP_ID, $created->getId());
        $this->assertSame('Inbound SMS', $created->getName());
        $this->assertSame(1, $created->getSmsTrunksCount());
    }

    public function testUpdateExternalReferenceId()
    {
        $group = SmsTrunkGroup::build(self::GROUP_ID);
        $group->setExternalReferenceId('updated-ext-ref-99');
        $updated = $group->save()->getData();

        $this->assertSame('updated-ext-ref-99', $updated->getExternalReferenceId());
    }

    public function testUpdateSmsTrunks()
    {
        $group = SmsTrunkGroup::build(self::GROUP_ID);
        $group->setSmsTrunks(new Collection([SmsTrunk::build(self::HTTP_IN_ID), SmsTrunk::build(self::SMPP_SMSC_ID)]));
        $updated = $group->save()->getData();

        $this->assertSame(2, $updated->getSmsTrunksCount());
    }

    public function testUpdateClearSmsTrunks()
    {
        $group = SmsTrunkGroup::build(self::GROUP_ID);
        $group->setSmsTrunks(new Collection());

        $this->assertSame([
            'type' => 'sms_trunk_groups',
            'id' => self::GROUP_ID,
            'relationships' => ['sms_trunks' => ['data' => []]],
        ], $group->toJsonApiPatchArray());
        $this->assertFalse($group->save()->hasErrors());
    }

    public function testDelete()
    {
        $this->assertFalse(SmsTrunkGroup::build(self::GROUP_ID)->delete()->hasErrors());
    }

    public function testDeleteInUse()
    {
        $document = SmsTrunkGroup::build('2b3c4d5e-0002-4b6c-9d7e-8f9a0b1c2d02')->delete();
        $this->assertTrue($document->hasErrors());
        $this->assertSame(422, $document->getResponse()->getStatusCode());
        $this->assertSame('base - Cannot delete an SMS trunk group while DIDs are routed to it or to its trunks', $document->getErrors()->first()->getDetail());
    }

    public function testSetters()
    {
        $group = new SmsTrunkGroup();
        $group->setName('group');
        $group->setExternalReferenceId(null);

        $this->assertSame(['name' => 'group', 'external_reference_id' => null], $group->toJsonApiArray()['attributes']);
        $this->assertNull($group->getSmsTrunksCount());
    }
}
