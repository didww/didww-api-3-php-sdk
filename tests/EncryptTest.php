<?php

namespace Didww\Tests;

class EncryptTest extends BaseTest
{
    public function testAll()
    {
        $this->startVCR('public_keys.yml');
        $encrypt = new \Didww\Encrypt();

        $fingerprint = $encrypt->getFingerprint();
        $this->assertEquals(
            'c74684d7863639169c21c4d04747f8d6fa05cfe3:::8a586bd37fa0000501715321b2e6a7b3ca57894c',
            $fingerprint
        );

        $publicKeysDocument = \Didww\Item\PublicKey::all();
        $publicKeys = $publicKeysDocument->getData();
        $keys = [
            $publicKeys[0]->getKey(),
            $publicKeys[1]->getKey(),
        ];

        $this->assertEquals($keys, $encrypt->getPublicKeys());

        $encryptedData = $encrypt->encrypt('test');
        $this->assertStringContainsString(':::', $encryptedData);
        $this->stopVCR();
    }
}
