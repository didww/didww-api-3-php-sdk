<?php

namespace Didww\Tests;

class EncryptTest extends BaseTest
{
    private function decrypt(string $binary, string $privateKey, int $keyIndex): string
    {
        $encryptedAesCredentials = 0 == $keyIndex ? substr($binary, 0, 512) : substr($binary, 512, 512);
        $encryptedAesData = substr($binary, 1024);
        $rsa = \phpseclib3\Crypt\RSA::load($privateKey)
            ->withHash('sha256')
            ->withMGFHash('sha256');

        $aesCredentials = $rsa->decrypt($encryptedAesCredentials);
        $aesKey = substr($aesCredentials, 0, 32);
        $aesIV = substr($aesCredentials, 32); // 16 bytes

        return openssl_decrypt($encryptedAesData, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $aesIV);
    }

    public function testAll()
    {
        $this->startVCR('public_keys.yml');
        $encrypt = new \Didww\Encrypt();

        $fingerprint = $encrypt->getFingerprint();
        $this->assertEquals(
            'ca5af2d14bee923a0a0d1687b7c77e7211a57f84:::683150ee69b4d906aa883d0ac12b0fdd79f95bcf',
            $fingerprint
        );

        $publicKeysDocument = \Didww\Item\PublicKey::all();
        $publicKeys = $publicKeysDocument->getData();
        $keys = [
            $publicKeys[0]->getKey(),
            $publicKeys[1]->getKey(),
        ];

        $this->assertEquals($keys, $encrypt->getPublicKeys());

        $data = 'test content';
        $encryptedData = $encrypt->encrypt($data);

        $privateKeys = json_decode(file_get_contents('tests/fixtures/private_keys.json'), true);
        $decryptedA = $this->decrypt($encryptedData, $privateKeys['private_key_a'], 0);
        $this->assertEquals($data, $decryptedA);

        $decryptedB = $this->decrypt($encryptedData, $privateKeys['private_key_b'], 1);
        $this->assertEquals($data, $decryptedB);

        $this->stopVCR();
    }
}
