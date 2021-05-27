<?php

namespace Didww\Tests;

class RequestValidatorTest extends BaseTest
{
    public function testSandbox()
    {
        $apiKey = 'SOMEAPIKEY';
        $signature = '18050028b6b22d0ed516706fba1c1af8d6a8f9d5';
        $validator = new \Didww\Callback\RequestValidator($apiKey);
        $url = 'http://example.com/callback.php?id=7ae7c48f-d48a-499f-9dc1-c9217014b457&reject_reason=&status=approved&type=address_verifications';
        $this->assertTrue($validator->validate(
            $url,
            [
                'status' => 'approved',
                'id' => '7ae7c48f-d48a-499f-9dc1-c9217014b457',
                'type' => 'address_verifications',
                'reject_reason' => '',
            ],
            $signature
        ));
    }

    public function testValidRequest()
    {
        $apiKey = 'SOMEAPIKEY';
        $signature = 'fe99e416c3547f2f59002403ec856ea386d05b2f';
        $validator = new \Didww\Callback\RequestValidator($apiKey);
        $this->assertTrue($validator->validate(
            'http://example.com/callbacks',
            [
                'status' => 'completed',
                'id' => '1dd7a68b-e235-402b-8912-fe73ee14243a',
                'type' => 'orders',
            ],
            $signature
        ));
    }

    public function testValidRequestWithQueryAndFragment()
    {
        $apiKey = 'OTHERAPIKEY';
        $signature = '32754ba93ac1207e540c0cf90371e7786b3b1cde';
        $validator = new \Didww\Callback\RequestValidator($apiKey);
        $this->assertTrue($validator->validate(
            'http://example.com/callbacks?foo=bar#baz',
            [
                'status' => 'completed',
                'id' => '1dd7a68b-e235-402b-8912-fe73ee14243a',
                'type' => 'orders',
            ],
            $signature
        ));
    }

    public function testEmptySignatureRequest()
    {
        $apiKey = 'SOMEAPIKEY';
        $signature = '';
        $validator = new \Didww\Callback\RequestValidator($apiKey);
        $this->assertFalse($validator->validate(
            'http://example.com/callbacks',
            [
                'status' => 'completed',
                'id' => '1dd7a68b-e235-402b-8912-fe73ee14243a',
                'type' => 'orders',
            ],
            $signature
        ));
    }

    public function testInvalidSignatureRequest()
    {
        $apiKey = 'SOMEAPIKEY';
        $signature = 'fbdb1d1b18aa6c08324b7d64b71fb76370690e1d';
        $validator = new \Didww\Callback\RequestValidator($apiKey);
        $this->assertFalse($validator->validate(
            'http://example.com/callbacks',
            [
                'status' => 'completed',
                'id' => '1dd7a68b-e235-402b-8912-fe73ee14243a',
                'type' => 'orders',
            ],
            $signature
        ));
    }
}
