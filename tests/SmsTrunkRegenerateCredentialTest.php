<?php

namespace Didww\Tests;

use Didww\Item\SmsTrunk;
use Didww\Item\SmsTrunkRegenerateCredential;

class SmsTrunkRegenerateCredentialTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'sms_trunk_regenerate_credentials.yml';
    }

    public function testCreate()
    {
        $credential = new SmsTrunkRegenerateCredential();
        $credential->setSmsTrunk(SmsTrunk::build('1a2b3c4d-0003-4a5b-8c6d-7e8f9a0b1c03'));
        $document = $credential->save();

        $data = $document->getData();
        $this->assertInstanceOf(SmsTrunkRegenerateCredential::class, $data);
        $this->assertSame('3c4d5e6f-0001-4c7d-8e9f-0a1b2c3d4e01', $data->getId());
        $this->assertSame([], $data->getAttributes());
    }

    public function testCreateError()
    {
        $credential = new SmsTrunkRegenerateCredential();
        $credential->setSmsTrunk(SmsTrunk::build('1a2b3c4d-0002-4a5b-8c6d-7e8f9a0b1c02'));
        $document = $credential->save();

        $this->assertTrue($document->hasErrors());
        $this->assertSame(422, $document->getResponse()->getStatusCode());
        $this->assertSame('/data/relationships/sms_trunk', $document->getErrors()->first()->getSource()->getPointer());
    }
}
