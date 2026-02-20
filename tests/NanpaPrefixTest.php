<?php

namespace Didww\Tests;

class NanpaPrefixTest extends BaseTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->startVCR('nanpa_prefixes.yml');
    }

    protected function tearDown(): void
    {
        $this->stopVCR();
    }

    public function testAll()
    {
        $nanpaPrefixDocument = \Didww\Item\NanpaPrefix::all();
        $this->assertEquals('2', $nanpaPrefixDocument->getMeta()['total_records']);
        $this->assertContainsOnlyInstancesOf('Didww\Item\NanpaPrefix', $nanpaPrefixDocument->getData());
    }

    public function testMetaData()
    {
        $nanpaPrefixDocument = \Didww\Item\NanpaPrefix::all();
        $this->assertEquals('2022-05-09', $nanpaPrefixDocument->getMeta()['api_version']);
    }

    public function testCount()
    {
        $nanpaPrefixDocument = \Didww\Item\NanpaPrefix::all();
        $this->assertCount(2, $nanpaPrefixDocument->getData());
    }

    public function testFind()
    {
        $uuid = '6c16d51d-d376-4395-91c4-012321317e48';
        $nanpaPrefixDocument = \Didww\Item\NanpaPrefix::find($uuid, ['include' => 'country']);
        $data = $nanpaPrefixDocument->getData();
        $this->assertEquals(['npa' => '864', 'nxx' => '920'], $data->getAttributes());
    }
}
