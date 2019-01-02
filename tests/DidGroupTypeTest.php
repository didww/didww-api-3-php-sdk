<?php

namespace Didww\Tests;

class DidGroupTypeTest extends BaseTest
{
    public function testAll()
    {
        $this->startVCR('did_group_types.yml');

        $didGroupTypesDocument = \Didww\Item\DidGroupType::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\DidGroupType', $didGroupTypesDocument->getData());

        $this->stopVCR();
    }

    public function testFind()
    {
        $this->startVCR('did_group_types.yml');

        $uuid = 'd6530a8c-924c-469a-98c0-9525602e6192';
        $didGroupTypeDocument = \Didww\Item\DidGroupType::find($uuid);

        $this->assertInstanceOf('Didww\Item\DidGroupType', $didGroupTypeDocument->getData());
        $this->assertEquals($didGroupTypeDocument->getData()->getAttributes(), [
            'name' => 'Global',
        ]);

        $this->stopVCR();
    }
}
