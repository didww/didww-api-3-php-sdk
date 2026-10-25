<?php

namespace Didww\Tests;

use Didww\Enum\SmsBodyType;
use Didww\Enum\SmsHttpMethod;
use Didww\Enum\SmsServerLocation;
use Didww\Item\SmsTrunk;
use Didww\Item\SmsTrunkConfiguration\Base;
use Didww\Item\SmsTrunkConfiguration\Generic;
use Didww\Item\SmsTrunkConfiguration\HttpIn;
use Didww\Item\SmsTrunkConfiguration\HttpOut;
use Didww\Item\SmsTrunkConfiguration\SmppEsme;
use Didww\Item\SmsTrunkConfiguration\SmppSmsc;
use Didww\Item\SmsTrunkConfiguration\SmsToEmail;
use PHPUnit\Framework\Attributes\DataProvider;

class SmsTrunkConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public static function roundTripProvider(): array
    {
        return [
            'sms_to_email' => [SmsToEmail::class, '{"type":"sms_to_email","attributes":{"send_to":"sms@example.com","subject":"Inbound SMS","message":"Hi"}}'],
            'http_in' => [HttpIn::class, '{"type":"http_in","attributes":{"url":"https:\/\/example.com\/sms","http_method":"put","query_parameters":{"source":"didww"},"headers":{"X-Source":"didww"},"body_type":"html_url_encoded","body":{"text":"{SMS_TEXT}"},"follow_redirect":true,"validate_https_certificate":false}}'],
            'http_in empty objects' => [HttpIn::class, '{"type":"http_in","attributes":{"url":"https:\/\/example.com\/sms","http_method":"post","query_parameters":{},"headers":{},"body_type":"html_multipart","body":{}}}'],
            'http_out' => [HttpOut::class, '{"type":"http_out","attributes":{"callback_url":"https:\/\/example.com\/status","allowed_ips":["203.0.113.10"]}}'],
            'smpp_esme' => [SmppEsme::class, '{"type":"smpp_esme","attributes":{"system_type":"cmt","allowed_ips":["203.0.113.10"],"server_location":"ams"}}'],
            'smpp_smsc' => [SmppSmsc::class, '{"type":"smpp_smsc","attributes":{"host":"smsc.example.com","tx_port":2775,"rx_port":2776,"transceiver_mode":false,"connection_timeout":0,"use_ssl":true,"system_id":"didww","system_type":"cmt","password":"secret","server_location":"nyc"}}'],
            'unknown' => [Generic::class, '{"type":"future_transport","attributes":{"endpoint":"https:\/\/example.com\/future","nested":{"a":[1,2],"b":{}}}}'],
        ];
    }

    #[DataProvider('roundTripProvider')]
    public function testFromArrayRoundTrip(string $expectedClass, string $json)
    {
        $configuration = Base::fromArray(json_decode($json, false));

        $this->assertInstanceOf($expectedClass, $configuration);
        $this->assertSame($json, json_encode($configuration->toJsonApiArray()));
    }

    public function testFromArrayAcceptsAssociativeArrays()
    {
        $configuration = Base::fromArray(['type' => 'smpp_smsc', 'attributes' => ['host' => 'smsc.example.com', 'server_location' => SmsServerLocation::AMS]]);

        $this->assertInstanceOf(SmppSmsc::class, $configuration);
        $this->assertSame('smsc.example.com', $configuration->getHost());
        $this->assertSame('ams', $configuration->getAttributes()['server_location']);
        $this->assertSame(SmsServerLocation::AMS, $configuration->getServerLocation());
    }

    public function testUnknownTypeBecomesGeneric()
    {
        $configuration = Base::fromArray(['type' => 'future_transport', 'attributes' => ['endpoint' => 'https://example.com/future']]);

        $this->assertInstanceOf(Generic::class, $configuration);
        $this->assertSame('future_transport', $configuration->getType());
        $this->assertSame(['endpoint' => 'https://example.com/future'], $configuration->getAttributes());
    }

    public function testGenericKeepsLargeNumbers()
    {
        $json = '{"type":"future_transport","attributes":{"id":9007199254740993,"nested":{"ids":[9007199254740993]},"ratio":0.1}}';
        $configuration = Base::fromArray(json_decode($json, false));

        $this->assertInstanceOf(Generic::class, $configuration);
        $this->assertSame(9007199254740993, $configuration->getAttribute('id'));
        $this->assertSame($json, json_encode($configuration->toJsonApiArray()));
    }

    public function testReadOnlyCredentialsAreNotSent()
    {
        $esme = Base::fromArray(json_decode('{"type":"smpp_esme","attributes":{"server_location":"nyc","system_id":"esme1234","password":"gen12345"}}', false));
        $this->assertSame('{"type":"smpp_esme","attributes":{"server_location":"nyc"}}', json_encode($esme->toJsonApiArray()));
        $this->assertSame('esme1234', $esme->getSystemId());
        $this->assertSame('gen12345', $esme->getPassword());

        $httpOut = Base::fromArray(json_decode('{"type":"http_out","attributes":{"callback_url":null,"allowed_ips":null,"auth_name":"n","auth_password":"p"}}', false));
        $this->assertSame('{"type":"http_out","attributes":{"callback_url":null,"allowed_ips":null}}', json_encode($httpOut->toJsonApiArray()));

        $credentialsOnly = new HttpOut(['auth_name' => 'n', 'auth_password' => 'p']);
        $this->assertSame('{"type":"http_out","attributes":{}}', json_encode($credentialsOnly->toJsonApiArray()));
        $this->assertSame($credentialsOnly->toJsonApiArray(), $credentialsOnly->toJsonApiArray());
    }

    public function testReadOnlyCredentialsAreNotSentFromSubclasses()
    {
        $httpOut = new class(['callback_url' => 'https://example.com/status', 'auth_password' => 'p']) extends HttpOut {
        };
        $esme = new class(['server_location' => 'nyc', 'password' => 'p']) extends SmppEsme {
        };

        $this->assertSame('{"type":"http_out","attributes":{"callback_url":"https:\/\/example.com\/status"}}', json_encode($httpOut->toJsonApiArray()));
        $this->assertSame('{"type":"smpp_esme","attributes":{"server_location":"nyc"}}', json_encode($esme->toJsonApiArray()));
    }

    public function testSmppSmscPasswordIsWritable()
    {
        $smsc = new SmppSmsc(['host' => 'smsc.example.com', 'password' => 'secret']);

        $this->assertSame('{"type":"smpp_smsc","attributes":{"host":"smsc.example.com","password":"secret"}}', json_encode($smsc->toJsonApiArray()));
    }

    public function testEmptyConfigurationIsSentAsObject()
    {
        $this->assertSame('{"type":"sms_to_email","attributes":{}}', json_encode((new SmsToEmail())->toJsonApiArray()));
    }

    public function testObjectAttributesAreSentAsObjects()
    {
        $httpIn = new HttpIn(['headers' => [], 'query_parameters' => ['0' => 'zero']]);
        $httpIn->setBody([]);
        $this->assertSame('{"type":"http_in","attributes":{"headers":{},"query_parameters":{"0":"zero"},"body":{}}}', json_encode($httpIn->toJsonApiArray()));

        $httpIn->setHeaders(['X-Source' => 'didww']);
        $httpIn->setQueryParameters(null);
        $httpIn->setBody('raw text');
        $this->assertSame(['X-Source' => 'didww'], $httpIn->getHeaders());
        $this->assertNull($httpIn->getQueryParameters());
        $this->assertSame('raw text', $httpIn->getBody());
        $this->assertSame('{"type":"http_in","attributes":{"headers":{"X-Source":"didww"},"query_parameters":null,"body":"raw text"}}', json_encode($httpIn->toJsonApiArray()));
    }

    public function testListAttributesAreSentAsListsFromTheConstructor()
    {
        $allowedIps = array_unique(['203.0.113.10', '203.0.113.10', '203.0.113.11']);

        $this->assertSame('{"type":"http_out","attributes":{"allowed_ips":["203.0.113.10","203.0.113.11"]}}', json_encode((new HttpOut(['allowed_ips' => $allowedIps]))->toJsonApiArray()));
        $this->assertSame('{"type":"smpp_esme","attributes":{"allowed_ips":["203.0.113.10","203.0.113.11"]}}', json_encode((new SmppEsme(['allowed_ips' => $allowedIps]))->toJsonApiArray()));
    }

    public function testNestedObjectGettersReturnArrays()
    {
        $httpIn = Base::fromArray(json_decode('{"type":"http_in","attributes":{"body":{"text":"{SMS_TEXT}","meta":{"a":1}}}}', false));

        $this->assertSame(['text' => '{SMS_TEXT}', 'meta' => ['a' => 1]], $httpIn->getBody());
    }

    public function testHttpInSetters()
    {
        $httpIn = new HttpIn();
        $httpIn->setUrl('https://example.com/sms');
        $httpIn->setHttpMethod('get');
        $httpIn->setBodyType(SmsBodyType::RAW);
        $httpIn->setBodyType(null);
        $httpIn->setFollowRedirect(true);
        $httpIn->setValidateHttpsCertificate(false);

        $this->assertSame(SmsHttpMethod::GET, $httpIn->getHttpMethod());
        $this->assertNull($httpIn->getBodyType());
        $this->assertSame([
            'url' => 'https://example.com/sms',
            'http_method' => 'get',
            'body_type' => null,
            'follow_redirect' => true,
            'validate_https_certificate' => false,
        ], $httpIn->getAttributes());

        $httpIn->setHttpMethod('patch');
        $this->assertSame('patch', $httpIn->getHttpMethod());
    }

    public function testHttpOutAndSmppEsmeSetters()
    {
        $httpOut = new HttpOut();
        $httpOut->setCallbackUrl(null);
        $httpOut->setAllowedIps(['a' => '203.0.113.10']);
        $this->assertSame(['callback_url' => null, 'allowed_ips' => ['203.0.113.10']], $httpOut->getAttributes());
        $this->assertNull($httpOut->getAuthName());

        $esme = new SmppEsme();
        $esme->setSystemType(null);
        $esme->setAllowedIps(null);
        $esme->setServerLocation('ams');
        $this->assertSame(SmsServerLocation::AMS, $esme->getServerLocation());
        $this->assertSame(['system_type' => null, 'allowed_ips' => null, 'server_location' => 'ams'], $esme->getAttributes());
    }

    public static function redactionProvider(): array
    {
        return [
            'http_out' => [
                new HttpOut(['callback_url' => 'https://example.com/status', 'auth_name' => 'generated-name', 'auth_password' => 'generated-password']),
                ['generated-name', 'generated-password'],
                ['https://example.com/status'],
            ],
            'smpp_esme' => [
                new SmppEsme(['server_location' => 'nyc', 'system_id' => 'esme1234', 'password' => 'gen12345']),
                ['esme1234', 'gen12345'],
                ['nyc'],
            ],
            'smpp_smsc' => [
                new SmppSmsc(['host' => 'smsc.example.com', 'system_id' => 'didww', 'password' => 'secret']),
                ['secret'],
                ['smsc.example.com', 'didww'],
            ],
            'http_in' => [
                new HttpIn(['url' => 'https://example.com/sms', 'headers' => ['Authorization' => 'Bearer abc123']]),
                ['Authorization', 'Bearer abc123'],
                ['https://example.com/sms'],
            ],
            'generic http_in' => [
                new Generic('http_in', ['url' => 'https://example.com/sms', 'headers' => ['Authorization' => 'Bearer abc123'], 'body' => null]),
                ['Authorization', 'Bearer abc123', 'https://example.com/sms'],
                ['http_in', 'url', 'headers', 'body'],
            ],
            'generic smpp_smsc' => [
                new Generic('smpp_smsc', ['host' => 'smsc.example.com', 'password' => 'secret']),
                ['smsc.example.com', 'secret'],
                ['smpp_smsc', 'host', 'password'],
            ],
        ];
    }

    #[DataProvider('redactionProvider')]
    public function testDebugOutputRedactsSecrets(Base $configuration, array $secrets, array $visible)
    {
        $trunk = new SmsTrunk();
        $trunk->setName('trunk');
        $trunk->setConfiguration($configuration);

        ob_start();
        var_dump($configuration);
        $varDump = ob_get_clean();
        foreach ([print_r($configuration, true), $varDump, print_r($trunk, true)] as $dump) {
            foreach ($secrets as $secret) {
                $this->assertStringNotContainsString($secret, $dump);
            }
            foreach ($visible as $value) {
                $this->assertStringContainsString($value, $dump);
            }
            $this->assertStringContainsString('[FILTERED]', $dump);
        }
    }

    public function testGenericDebugOutputKeepsNamesAndNulls()
    {
        $generic = new Generic('http_in', ['url' => 'https://example.com/sms', 'body' => null, 'retries' => 3]);

        $this->assertSame([
            'type' => 'http_in',
            'attributes' => ['url' => '[FILTERED]', 'body' => null, 'retries' => '[FILTERED]'],
        ], $generic->__debugInfo());
    }

    public function testRedactionKeepsWireValues()
    {
        $smsc = new SmppSmsc(['host' => 'smsc.example.com', 'password' => 'secret']);
        $this->assertSame('secret', $smsc->toJsonApiArray()['attributes']['password']);

        $generic = new Generic('smpp_smsc', ['password' => 'secret', 'system_type' => null]);
        $this->assertSame('{"type":"smpp_smsc","attributes":{"password":"secret","system_type":null}}', json_encode($generic->toJsonApiArray()));

        $trunk = new SmsTrunk();
        $trunk->setConfiguration(new HttpIn(['headers' => ['Authorization' => 'Bearer abc123']]));
        $this->assertSame(['Authorization' => 'Bearer abc123'], (array) $trunk->toJsonApiArray()['attributes']['configuration']['attributes']['headers']);
    }

    public function testGenericSetAttribute()
    {
        $generic = new Generic(null);
        $generic->setAttribute('server_location', SmsServerLocation::NYC);
        $generic->setAttribute('body', null);

        $this->assertSame('unknown', $generic->getType());
        $this->assertSame(['server_location' => 'nyc', 'body' => null], $generic->getAttributes());
        $this->assertNull($generic->getAttribute('missing'));
    }

    public function testGenericGettersReturnCopies()
    {
        $trunk = new SmsTrunk(['name' => 'Future', 'configuration' => (object) ['type' => 'future_transport', 'attributes' => (object) ['opts' => (object) ['retries' => 1]]]]);
        $trunk->setId('9c6f3b4e-2d1a-4b7e-8f0a-1c2d3e4f5a6b');
        $trunk->syncPersistedState();
        $configuration = $trunk->getConfiguration();

        $opts = $configuration->getAttribute('opts');
        $opts->retries = 5;
        $attributes = $configuration->getAttributes();
        $attributes['opts']->retries = 7;

        $this->assertSame(1, $configuration->getAttribute('opts')->retries);
        $this->assertArrayNotHasKey('attributes', $trunk->toJsonApiPatchArray());

        $configuration->setAttribute('opts', $opts);

        $this->assertSame('{"type":"future_transport","attributes":{"opts":{"retries":5}}}', json_encode($trunk->toJsonApiPatchArray()['attributes']['configuration']));
    }
}
