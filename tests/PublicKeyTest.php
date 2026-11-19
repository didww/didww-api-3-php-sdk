<?php

namespace Didww\Tests;

class PublicKeyTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'public_keys.yml';
    }

    public function testAll()
    {
        $publicKeysDocument = \Didww\Item\PublicKey::all();
        $publicKeys = $publicKeysDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\PublicKey', $publicKeys);

        $this->assertStringContainsString('PUBLIC KEY', $publicKeys[0]->getKey());
        $this->assertStringContainsString('PUBLIC KEY', $publicKeys[1]->getKey());

        $this->assertCount(2, $publicKeys);
    }
}
