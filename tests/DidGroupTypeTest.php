<?php

namespace Didww\Tests;

class DidGroupTypeTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'did_group_types.yml';
    }

    public function testAll()
    {
        $didGroupTypesDocument = \Didww\Item\DidGroupType::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\DidGroupType', $didGroupTypesDocument->getData());
    }

    public function testFind()
    {
        $uuid = 'd6530a8c-924c-469a-98c0-9525602e6192';
        $didGroupTypeDocument = \Didww\Item\DidGroupType::find($uuid);

        $this->assertInstanceOf('Didww\Item\DidGroupType', $didGroupTypeDocument->getData());
        $this->assertEquals($didGroupTypeDocument->getData()->getAttributes(), [
            'name' => 'Global',
        ]);
    }
}
