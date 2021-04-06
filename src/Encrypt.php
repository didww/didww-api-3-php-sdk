<?php

namespace Didww;

use Didww\Item\PublicKey;

class Encrypt
{
    private $publicKeys;
    private $fingerprint;

    public static function encryptWithKeys(string $binary, array $publicKeys): string
    {
        $aesKey = openssl_random_pseudo_bytes(32); // 256 bits => 32 bytes
        $aesIV = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $salt = openssl_random_pseudo_bytes(16); // 16 bytes salt
        $dataUriBinary = 'data:application/octet-stream;base64,'.base64_encode($binary);

        $encrypted_aes = openssl_encrypt($dataUriBinary, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $aesIV);

        $aes_credentials = bin2hex($aesKey).':::'.bin2hex($aesIV);
        $encrypted_rsa_a = Encrypt::encryptRsaOaep($publicKeys[0], $aes_credentials);
        $encrypted_rsa_b = Encrypt::encryptRsaOaep($publicKeys[1], $aes_credentials);

        return join(':::', [
            base64_encode($encrypted_rsa_a),
            base64_encode($encrypted_rsa_b),
            base64_encode($salt.$encrypted_aes),
        ]);
    }

    public static function calculateFingerprint(array $publicKeys): string
    {
        return join(':::', [
            Encrypt::fingerprintFor($publicKeys[0]),
            Encrypt::fingerprintFor($publicKeys[1]),
        ]);
    }

    private static function fingerprintFor(string $publicKey): string
    {
        $publicKeyBase64 = Encrypt::normalizePublicKey($publicKey);
        $publicKeyBin = base64_decode($publicKeyBase64);
        $digest = sha1($publicKeyBin, true);

        return bin2hex($digest);
    }

    private static function encryptRsaOaep(string $publicKey, string $text): string
    {
        $rsa = \phpseclib3\Crypt\PublicKeyLoader::load($publicKey)
            ->withHash('sha256')
            ->withMGFHash('sha256');

        return $rsa->encrypt($text);
    }

    private static function normalizePublicKey($publicKey): string
    {
        if ("\n" != $publicKey[-1]) {
            $publicKey .= "\n";
        }
        $publicKeyArray = explode("\n", $publicKey);

        return implode('', array_slice($publicKeyArray, 1, count($publicKeyArray) - 3));
    }

    public function __construct()
    {
        $publicKeysData = PublicKey::all()->getData();
        $this->publicKeys = [
            $publicKeysData[0]->getKey(),
            $publicKeysData[1]->getKey(),
        ];
        $this->fingerprint = Encrypt::calculateFingerprint($this->publicKeys);
    }

    public function encrypt(string $binary): string
    {
        return Encrypt::encryptWithKeys($binary, $this->publicKeys);
    }

    public function getPublicKeys(): array
    {
        return $this->publicKeys;
    }

    public function getFingerprint(): string
    {
        return $this->fingerprint;
    }
}
