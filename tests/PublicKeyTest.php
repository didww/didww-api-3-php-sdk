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

    public function testPublicKeysRequestOmitsApiKeyHeader()
    {
        [$client, $buildRequest] = $this->getClientAndBuildRequest();

        $request = $buildRequest->invoke($client, 'GET', '/public_keys');
        $this->assertFalse($request->hasHeader('api-key'), 'public_keys request should not include api-key header');
    }

    public function testOtherRequestsIncludeApiKeyHeader()
    {
        [$client, $buildRequest] = $this->getClientAndBuildRequest();

        $request = $buildRequest->invoke($client, 'GET', '/dids');
        $this->assertTrue($request->hasHeader('api-key'), 'non-public_keys request should include api-key header');
    }

    private function getClientAndBuildRequest(): array
    {
        $client = new \Didww\Client();
        $client->setApiKey('test-api-key');

        $buildRequest = new \ReflectionMethod($client, 'buildRequest');

        return [$client, $buildRequest];
    }
}
