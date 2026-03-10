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

    public function testPublicKeysRequestOmitsApiKeyHeader()
    {
        $documentClient = \Didww\Configuration::getDocumentClient();
        $reflection = new \ReflectionProperty($documentClient, 'client');
        $reflection->setAccessible(true);
        $client = $reflection->getValue($documentClient);

        $buildRequest = new \ReflectionMethod($client, 'buildRequest');
        $buildRequest->setAccessible(true);

        $request = $buildRequest->invoke($client, 'GET', 'public_keys');
        $this->assertFalse($request->hasHeader('api-key'), 'public_keys request should not include api-key header');
    }

    public function testOtherRequestsIncludeApiKeyHeader()
    {
        $documentClient = \Didww\Configuration::getDocumentClient();
        $reflection = new \ReflectionProperty($documentClient, 'client');
        $reflection->setAccessible(true);
        $client = $reflection->getValue($documentClient);

        $buildRequest = new \ReflectionMethod($client, 'buildRequest');
        $buildRequest->setAccessible(true);

        $request = $buildRequest->invoke($client, 'GET', 'dids');
        $this->assertTrue($request->hasHeader('api-key'), 'non-public_keys request should include api-key header');
    }
}
