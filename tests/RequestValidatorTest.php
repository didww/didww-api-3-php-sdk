<?php

namespace Didww\Tests;

class RequestValidatorTest extends BaseTest
{
    public function testSandbox()
    {
        $apiKey = 'SOMEAPIKEY';
        $signature = '18050028b6b22d0ed516706fba1c1af8d6a8f9d5';
        $validator = new \Didww\Callback\RequestValidator($apiKey);
        $url = 'http://example.com/callback.php?id=7ae7c48f-d48a-499f-9dc1-c9217014b457&reject_reason=&status=approved&type=address_verifications'; // NOSONAR
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
            'http://example.com/callbacks', // NOSONAR
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
            'http://example.com/callbacks?foo=bar#baz', // NOSONAR
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
            'http://example.com/callbacks', // NOSONAR
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
            'http://example.com/callbacks', // NOSONAR
            [
                'status' => 'completed',
                'id' => '1dd7a68b-e235-402b-8912-fe73ee14243a',
                'type' => 'orders',
            ],
            $signature
        ));
    }

    public static function urlNormalizationProvider(): array
    {
        return [
            'http plain' => ['http://foo.com/bar', '4d1ce2be656d20d064183bec2ab98a2ff3981f73'], // NOSONAR
            'http default port 80' => ['http://foo.com:80/bar', '4d1ce2be656d20d064183bec2ab98a2ff3981f73'], // NOSONAR
            'http port 443' => ['http://foo.com:443/bar', '904eaa65c0759afac0e4d8912de424e2dfb96ea1'], // NOSONAR
            'http port 8182' => ['http://foo.com:8182/bar', 'eb8fcfb3d7ed4b4c2265d73cf93c31ba614384d1'], // NOSONAR

            'http with query' => ['http://foo.com/bar?baz=boo', '78b00717a86ce9df06abf45ff818aa94537e1729'], // NOSONAR
            'http with userinfo' => ['http://user:pass@foo.com/bar', '88615a11a78c021c1da2e1e0bfb8cc165170afc5'], // NOSONAR
            'http with fragment' => ['http://foo.com/bar#test', 'b1c4391fcdab7c0521bb5b9eb4f41f08529b8418'], // NOSONAR
            'https plain' => ['https://foo.com/bar', 'f26a771c302319a7094accbe2989bad67fff2928'], // NOSONAR
            'https default port 443' => ['https://foo.com:443/bar', 'f26a771c302319a7094accbe2989bad67fff2928'], // NOSONAR
            'https port 80' => ['https://foo.com:80/bar', 'bd45af5253b72f6383c6af7dc75250f12b73a4e1'], // NOSONAR
            'https port 8384' => ['https://foo.com:8384/bar', '9c9fec4b7ebd6e1c461cb8e4ffe4f2987a19a5d3'], // NOSONAR
            'https with query' => ['https://foo.com/bar?qwe=asd', '4a0e98ddf286acadd1d5be1b0ed85a4e541c3137'], // NOSONAR
            'https with userinfo' => ['https://qwe:asd@foo.com/bar', '7a8cd4a6c349910dfecaf9807e56a63787250bbd'], // NOSONAR
            'https with fragment' => ['https://foo.com/bar#baz', '5024919770ea5ca2e3ccc07cb940323d79819508'], // NOSONAR

            'ipv6 http default port' => ['http://[::1]/bar', 'e0e9b83e4046d097f54b3ae64b08cbb4a539f601'], // NOSONAR
            'ipv6 http explicit port 80' => ['http://[::1]:80/bar', 'e0e9b83e4046d097f54b3ae64b08cbb4a539f601'], // NOSONAR
            'ipv6 http custom port' => ['http://[::1]:9090/bar', 'ebec110ec5debd0e0fd086ff2f02e48ca665b543'], // NOSONAR
            'ipv6 https default port' => ['https://[::1]/bar', 'f3cfe6f523fdf1d4eaadc310fcd3ed92e1e324b0'], // NOSONAR
            'empty path' => ['http://foo.com', '6e9bb224f621d9bf735e80b45d69af688900e7d2'], // NOSONAR
            'explicit slash' => ['http://foo.com/', '6e9bb224f621d9bf735e80b45d69af688900e7d2'], // NOSONAR
            'percent-encoded path' => ['http://foo.com/hello%20world', 'eb64035b2e8f356ff1442898a39ec94d5c3e2fc8'], // NOSONAR
            'percent-encoded slash in path' => ['http://foo.com/foo%2Fbar', 'db24428442b012fa0972a453ba1ba98e755bba10'], // NOSONAR
        ];
    }

    /**
     * @dataProvider urlNormalizationProvider
     */
    public function testUrlNormalization(string $url, string $expectedSignature)
    {
        $apiKey = 'SOMEAPIKEY';
        $validator = new \Didww\Callback\RequestValidator($apiKey);
        $this->assertTrue($validator->validate(
            $url,
            [
                'id' => '1dd7a68b-e235-402b-8912-fe73ee14243a',
                'status' => 'completed',
                'type' => 'orders',
            ],
            $expectedSignature
        ));
    }
}
