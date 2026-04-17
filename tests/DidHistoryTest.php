<?php

namespace Didww\Tests;

class DidHistoryTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'did_history.yml';
    }

    public function testAllDidHistory()
    {
        $document = \Didww\Item\DidHistory::all();
        $data = $document->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\DidHistory', $data);
        $this->assertCount(2, $data);

        $first = $data[0];
        $this->assertEquals('11111111-2222-3333-4444-555555555555', $first->getId());
        $this->assertEquals('442038680521', $first->getDidNumber());
        $this->assertEquals('assigned', $first->getAction());
        $this->assertEquals('api3', $first->getMethod());
        $this->assertInstanceOf(\DateTime::class, $first->getCreatedAt());
    }

    public function testFindDidHistory()
    {
        $uuid = '01234567-89ab-cdef-0123-456789abcdef';
        $document = \Didww\Item\DidHistory::find($uuid);

        $data = $document->getData();
        $this->assertInstanceOf('Didww\Item\DidHistory', $data);
        $this->assertEquals($uuid, $data->getId());
        $this->assertEquals('442038680521', $data->getDidNumber());
        $this->assertEquals('renewed', $data->getAction());
        $this->assertEquals('system', $data->getMethod());
        $this->assertInstanceOf(\DateTime::class, $data->getCreatedAt());
    }
}
