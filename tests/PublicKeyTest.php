<?php

namespace Didww\Tests;

class PublicKeyTest extends BaseTest
{
    public function testAll()
    {
        $this->startVCR('public_keys.yml');
        $publicKeysDocument = \Didww\Item\PublicKey::all();
        $publicKeys = $publicKeysDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\PublicKey', $publicKeys);

        $this->assertStringContainsString('PUBLIC KEY', $publicKeys[0]->getKey());
        $this->assertStringContainsString('PUBLIC KEY', $publicKeys[1]->getKey());

        $this->assertCount(2, $publicKeys);
        $this->stopVCR();
    }
}
