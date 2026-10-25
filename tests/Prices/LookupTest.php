<?php

namespace Didww\Tests\Prices;

use Didww\Auth\Audience;
use Didww\Auth\TokenProvider;
use Didww\Exception\AuthErrorKind;
use Didww\Exception\DidwwAuthException;
use Didww\Exception\DidwwPricesException;
use Didww\Exception\PricesErrorKind;
use Didww\Prices;
use Didww\Prices\PricesService;
use Didww\Prices\SmsInPrice;
use Didww\Prices\SmsOutPrice;
use Didww\Prices\VoiceInPrice;
use Didww\Prices\VoiceOutPrice;
use Didww\Prices\VoiceOutPriceCategory;
use Didww\Tests\Prices\Support\PricesHelpers;
use Didww\Tests\Support\MockHttp;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LookupTest extends TestCase
{
    use PricesHelpers;

    protected function tearDown(): void
    {
        TokenProvider::forgetRateLimits();
    }

    public function testVoiceInLookup()
    {
        $this->configureMock()->append(self::voiceInToken(), self::pricesJson(200, self::fixture('voice_in/lookup/200.json')));

        $result = Prices::voiceIn()->lookup(['number' => '18005550123']);

        $requests = $this->serviceRequests();
        $this->assertCount(1, $requests);
        $request = $requests[0];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://api-vin-sandbox.didww.com/v3/lookup/prices', (string) $request->getUri()->withQuery(''));
        $this->assertSame(['filter[number]' => '18005550123'], self::query($request));
        $this->assertSame('application/vnd.api+json', $request->getHeaderLine('Accept'));
        $this->assertSame('Bearer token-1', $request->getHeaderLine('Authorization'));
        $this->assertFalse($request->hasHeader('Api-Key'));
        $this->assertStringStartsWith('didww-php-sdk/', $request->getHeaderLine('User-Agent'));
        $this->assertSame([Audience::VOICE_IN], $this->mintAudiences());
        $this->assertEquals(60, $this->serviceOptions()[0]['timeout']);

        $this->assertSame(2, $result->getTotalRecords());
        $this->assertCount(2, $result);
        [$priced, $rejected] = $result->getRows();
        $this->assertInstanceOf(VoiceInPrice::class, $priced);
        $this->assertSame('df73d9e4-46ce-4af0-817e-f090dc8144f1', $priced->getId());
        $this->assertSame('1800', $priced->getDestination());
        $this->assertSame('United States', $priced->getCountry());
        $this->assertNull($priced->getSrcCountryName());
        $this->assertSame('cellular', $priced->getSourceType());
        $this->assertSame(0.025, $priced->getRate());
        $this->assertSame(0.0, $priced->getConnectionFee());
        $this->assertSame(60, $priced->getInitialInterval());
        $this->assertSame(60, $priced->getNextInterval());
        $this->assertFalse($priced->getRejectCalls());
        $this->assertSame('cb1335e1-6cfd-4921-8724-153d9ed67ac3', $rejected->getId());
        $this->assertSame('United Kingdom', $rejected->getSrcCountryName());
        $this->assertNull($rejected->getRate());
        $this->assertNull($rejected->getConnectionFee());
        $this->assertTrue($rejected->getRejectCalls());
        $this->assertSame([$priced, $rejected], iterator_to_array($result));
    }

    public function testVoiceInLookupWithSourceCountry()
    {
        $this->configureMock()->append(self::voiceInToken(), self::pricesJson(200, self::fixture('voice_in/lookup/200.json')));

        Prices::voiceIn()->lookup(['src_country_iso' => 'GB', 'number' => '448001234567']);

        $request = $this->serviceRequests()[0];
        $this->assertSame('filter%5Bnumber%5D=448001234567&filter%5Bsrc_country_iso%5D=GB', $request->getUri()->getQuery());
    }

    public function testFilterValuesAreEncoded()
    {
        $this->configureMock()->append(self::voiceInToken(), self::pricesJson(200, self::fixture('voice_in/lookup/200.json')));
        $value = new class {
            public function __toString(): string
            {
                return '+1 800&x';
            }
        };

        Prices::voiceIn()->lookup(['number' => $value, 'src_country_iso' => null]);

        $this->assertSame('filter%5Bnumber%5D=%2B1%20800%26x', $this->serviceRequests()[0]->getUri()->getQuery());
    }

    public function testIntegerFilterValue()
    {
        $this->configureMock()->append(self::voiceInToken(), self::pricesJson(200, self::fixture('voice_in/lookup/200.json')));

        Prices::voiceIn()->lookup(['number' => 18005550123]);

        $this->assertSame(['filter[number]' => '18005550123'], self::query($this->serviceRequests()[0]));
    }

    public function testFilterValueOfWrongType()
    {
        $this->configureMock();

        try {
            Prices::voiceIn()->lookup(['number' => ['18005550123']]);
            $this->fail('expected an exception');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Filter number must be a string, got array', $e->getMessage());
        }
        $this->assertSame(0, $this->http->count());
    }

    public function testVoiceOutLookup()
    {
        $this->configureMock()->append(self::tokenFor(Audience::VOICE_OUT), self::pricesJson(200, self::fixture('voice_out/lookup/200.json')));

        $result = Prices::voiceOut()->lookup(['number' => '493012345678']);

        $request = $this->serviceRequests()[0];
        $this->assertSame('https://api-vout-sandbox.didww.com/v3/lookup/prices', (string) $request->getUri()->withQuery(''));
        $this->assertSame(['filter[number]' => '493012345678'], self::query($request));
        $this->assertSame([Audience::VOICE_OUT], $this->mintAudiences());

        $this->assertSame(2, $result->getTotalRecords());
        [$scheduled, $plain] = $result->getRows();
        $this->assertInstanceOf(VoiceOutPrice::class, $scheduled);
        $this->assertSame('7b82cac2-c42a-4fa9-8da4-ccba2843068f', $scheduled->getId());
        $this->assertSame(VoiceOutPriceCategory::INTERNATIONAL, $scheduled->getCategory());
        $this->assertSame('Germany', $scheduled->getCountry());
        $this->assertSame('Fixed', $scheduled->getNetwork());
        $this->assertSame('4930', $scheduled->getPrefix());
        $this->assertSame(0.0045, $scheduled->getRate());
        $this->assertSame(0.0, $scheduled->getConnectionFee());
        $this->assertSame(1, $scheduled->getInitialIncrement());
        $this->assertSame(1, $scheduled->getRecurringIncrement());
        $this->assertFalse($scheduled->getRejectCalls());
        $this->assertSame(0.005, $scheduled->getNextRate());
        $this->assertSame(0.0, $scheduled->getNextConnectionFee());
        $this->assertSame(30, $scheduled->getNextInitialIncrement());
        $this->assertSame(6, $scheduled->getNextRecurringIncrement());
        $this->assertInstanceOf(\DateTimeImmutable::class, $scheduled->getEffectiveAt());
        $this->assertSame(1793491200, $scheduled->getEffectiveAt()->getTimestamp());
        $this->assertSame('Increase and Billing increment change', $scheduled->getComment());
        $this->assertSame(VoiceOutPriceCategory::ORIGIN_BASED, $plain->getCategory());
        $this->assertNull($plain->getNextRate());
        $this->assertNull($plain->getNextConnectionFee());
        $this->assertNull($plain->getNextInitialIncrement());
        $this->assertNull($plain->getNextRecurringIncrement());
        $this->assertNull($plain->getEffectiveAt());
        $this->assertNull($plain->getComment());
    }

    public function testUnknownVoiceOutCategoryKeepsRawValue()
    {
        $body = str_replace('"Origin based"', '"Premium"', self::fixture('voice_out/lookup/200.json'));
        $this->configureMock()->append(self::tokenFor(Audience::VOICE_OUT), self::pricesJson(200, $body));

        $rows = Prices::voiceOut()->lookup(['number' => '493012345678'])->getRows();

        $this->assertSame('Premium', $rows[1]->getCategory());
    }

    public static function smsServiceProvider(): array
    {
        return [
            'SMS OUT A2P' => ['smsOutA2p', '/v3/sms_out/a2p/lookup/prices'],
            'SMS OUT P2P' => ['smsOutP2p', '/v3/sms_out/p2p/lookup/prices'],
            'SMS OUT OTP' => ['smsOutOtp', '/v3/sms_out/otp/lookup/prices'],
            'SMS IN' => ['smsIn', '/v3/sms_in/lookup/prices'],
        ];
    }

    #[DataProvider('smsServiceProvider')]
    public function testSmsLookups(string $service, string $path)
    {
        $fixture = 'smsIn' === $service ? 'sms_in/lookup/200.json' : 'sms_out/lookup/200.json';
        $this->configureMock()->append(
            self::tokenFor(Audience::SMS),
            self::pricesJson(200, self::fixture($fixture)),
            self::pricesJson(200, self::fixture($fixture)),
            self::pricesJson(200, self::fixture($fixture)),
        );

        Prices::$service()->lookup(['dst_number' => '4915123456789']);
        Prices::$service()->lookup(['dst_country_iso' => 'FR']);
        Prices::$service()->lookup(['dst_number' => '4915123456789', 'dst_country_iso' => 'DE']);

        $requests = $this->serviceRequests();
        $this->assertCount(3, $requests);
        foreach ($requests as $request) {
            $this->assertSame('api-sms-sandbox.didww.com', $request->getUri()->getHost());
            $this->assertSame($path, $request->getUri()->getPath());
            $this->assertSame('Bearer token-1', $request->getHeaderLine('Authorization'));
        }
        $this->assertSame(['filter[dst_number]' => '4915123456789'], self::query($requests[0]));
        $this->assertSame(['filter[dst_country_iso]' => 'FR'], self::query($requests[1]));
        $this->assertSame(['filter[dst_country_iso]' => 'DE', 'filter[dst_number]' => '4915123456789'], self::query($requests[2]));
        $this->assertSame([Audience::SMS], $this->mintAudiences());
    }

    public function testSmsOutLookupRows()
    {
        $this->configureMock()->append(self::tokenFor(Audience::SMS), self::pricesJson(200, self::fixture('sms_out/lookup/200.json')));

        $result = Prices::smsOutA2p()->lookup(['dst_number' => '4915123456789']);

        $this->assertSame(2, $result->getTotalRecords());
        [$priced, $rejected] = $result->getRows();
        $this->assertInstanceOf(SmsOutPrice::class, $priced);
        $this->assertSame('d8e4d372-42a6-4bb6-80ec-6c29c49aa9fd', $priced->getId());
        $this->assertNull($priced->getSourcePrefix());
        $this->assertSame('49151', $priced->getDestinationPrefix());
        $this->assertSame('Germany', $priced->getCountry());
        $this->assertSame(262, $priced->getMcc());
        $this->assertSame('Germany', $priced->getMccName());
        $this->assertSame(1, $priced->getMnc());
        $this->assertSame('Telekom Deutschland', $priced->getMncName());
        $this->assertFalse($priced->getRejectSms());
        $this->assertSame(0.075, $priced->getPrice());
        $this->assertTrue($rejected->getRejectSms());
        $this->assertNull($rejected->getPrice());
    }

    public function testSmsInLookupRows()
    {
        $this->configureMock()->append(self::tokenFor(Audience::SMS), self::pricesJson(200, self::fixture('sms_in/lookup/200.json')));

        $result = Prices::smsIn()->lookup(['dst_number' => '12025550123']);

        $this->assertSame(1, $result->getTotalRecords());
        $price = $result->getRows()[0];
        $this->assertInstanceOf(SmsInPrice::class, $price);
        $this->assertSame('e93f925e-25ee-4060-bd50-a790b4945d3f', $price->getId());
        $this->assertNull($price->getSourcePrefix());
        $this->assertSame(1, $price->getSourceNumberLengthMin());
        $this->assertSame(15, $price->getSourceNumberLengthMax());
        $this->assertSame('1', $price->getDestinationPrefix());
        $this->assertSame(11, $price->getDestinationNumberLengthMin());
        $this->assertSame(11, $price->getDestinationNumberLengthMax());
        $this->assertSame('United States', $price->getCountry());
        $this->assertSame(0.005, $price->getPrice());
    }

    public static function filterValidationProvider(): array
    {
        return [
            'Voice IN without number' => ['voiceIn', ['src_country_iso' => 'GB'], 'Filter number is required on lookup/prices'],
            'Voice IN with a blank number' => ['voiceIn', ['number' => ''], 'Filter number is required on lookup/prices'],
            'Voice IN with a null number' => ['voiceIn', ['number' => null], 'Filter number is required on lookup/prices'],
            'Voice IN with an unknown filter' => ['voiceIn', ['number' => '18005550123', 'dst_country_iso' => 'US'],
                'Filter dst_country_iso is not allowed on lookup/prices (allowed: number, src_country_iso)'],
            'Voice OUT with src_country_iso' => ['voiceOut', ['number' => '493012345678', 'src_country_iso' => 'DE'],
                'Filter src_country_iso is not allowed on lookup/prices (allowed: number)'],
            'Voice OUT with unknown filters' => ['voiceOut', ['number' => '493012345678', 'b' => '1', 'a' => '1'],
                'Filter a, b is not allowed on lookup/prices (allowed: number)'],
            'Voice OUT with numeric filter names' => ['voiceOut', ['number' => '493012345678', 10 => '1', 9 => '1'],
                'Filter 10, 9 is not allowed on lookup/prices (allowed: number)'],
            'Unknown filter before missing filter' => ['voiceOut', ['nmber' => '493012345678'],
                'Filter nmber is not allowed on lookup/prices (allowed: number)'],
            'SMS OUT A2P without filters' => ['smsOutA2p', [], 'One of filters dst_number, dst_country_iso is required on sms_out/a2p/lookup/prices'],
            'SMS OUT P2P without filters' => ['smsOutP2p', [], 'One of filters dst_number, dst_country_iso is required on sms_out/p2p/lookup/prices'],
            'SMS OUT OTP with a blank dst_number' => ['smsOutOtp', ['dst_number' => ' '],
                'One of filters dst_number, dst_country_iso is required on sms_out/otp/lookup/prices'],
            'SMS IN with number' => ['smsIn', ['number' => '12025550123'],
                'Filter number is not allowed on sms_in/lookup/prices (allowed: dst_number, dst_country_iso)'],
        ];
    }

    #[DataProvider('filterValidationProvider')]
    public function testFilterValidation(string $service, array $filters, string $message)
    {
        $this->configureMock();

        try {
            Prices::$service()->lookup($filters);
            $this->fail('expected an exception');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame($message, $e->getMessage());
        }
        $this->assertSame(0, $this->http->count(), 'no token and no lookup request');
    }

    public static function lookupErrorProvider(): array
    {
        return [
            '400' => [400, self::fixture('errors/filter_not_allowed.json'), null, PricesErrorKind::BAD_REQUEST, '102', null,
                'Prices service: src_country_iso is not allowed.'],
            '403' => [403, '{"errors":[{"title":"Insufficient scope","code":"403","status":"403"}]}', null, PricesErrorKind::ACCESS_DENIED, '403', null,
                'Prices service: Insufficient scope'],
            '404 price_not_found' => [404, self::fixture('errors/price_not_found.json'), null, PricesErrorKind::PRICE_NOT_FOUND, 'price_not_found', null,
                'Prices service: No effective price covers this number in the assigned plan.'],
            '404 price_plan_not_assigned' => [404, self::fixture('errors/price_plan_not_assigned.json'), null, PricesErrorKind::PRICE_PLAN_NOT_ASSIGNED,
                'price_plan_not_assigned', null, 'Prices service: No price plan is assigned to this account.'],
            '404 other code' => [404, '{"errors":[{"title":"Not found","code":"not_found"}]}', null, PricesErrorKind::PRICE_PLAN_NOT_ASSIGNED, 'not_found', null,
                'Prices service: Not found'],
            '404 without body' => [404, '', null, PricesErrorKind::PRICE_PLAN_NOT_ASSIGNED, 'price_plan_not_assigned', null,
                'Prices service responded with status 404'],
            '406' => [406, '{"errors":[{"title":"Not acceptable","code":"406","status":"406"}]}', null, PricesErrorKind::NOT_ACCEPTABLE, '406', null,
                'Prices service: Not acceptable'],
            '429' => [429, '{"errors":[{"title":"Too many requests","code":"too_many_requests","status":"429"}]}', '7', PricesErrorKind::TOO_MANY_REQUESTS,
                'too_many_requests', 7, 'Prices service: Too many requests'],
            '503 not retried on lookups' => [503, self::fixture('errors/serving_failure.json'), '1', PricesErrorKind::SERVICE_UNAVAILABLE, 'serving_failure', 1,
                'Prices service: The price file is not available yet.'],
            '500' => [500, '', null, PricesErrorKind::UNEXPECTED_STATUS, null, null, 'Prices service responded with status 500'],
            '500 with a non-JSON body' => [500, '<html>oops</html>', null, PricesErrorKind::UNEXPECTED_STATUS, null, null,
                'Prices service responded with status 500'],
            '304' => [304, '', null, PricesErrorKind::UNEXPECTED_STATUS, null, null, 'Prices service responded with status 304'],
            'Retry-After as an HTTP date' => [429, '', 'Wed, 21 Oct 2026 07:28:00 GMT', PricesErrorKind::TOO_MANY_REQUESTS, null, null,
                'Prices service responded with status 429'],
        ];
    }

    #[DataProvider('lookupErrorProvider')]
    public function testLookupErrors(int $status, string $body, ?string $retryAfter, PricesErrorKind $kind, ?string $code, ?int $wait, string $message)
    {
        $this->configureMock()->append(self::voiceInToken(), self::pricesJson($status, $body, null === $retryAfter ? [] : ['Retry-After' => $retryAfter]));

        try {
            Prices::voiceIn()->lookup(['number' => '18005550123']);
            $this->fail('expected an exception');
        } catch (DidwwPricesException $e) {
            $this->assertSame($kind, $e->getKind());
            $this->assertSame($status, $e->getStatusCode());
            $this->assertSame($code, $e->getErrorCode());
            $this->assertSame($wait, $e->getRetryAfter());
            $this->assertSame($message, $e->getMessage());
            $this->assertSame(0, $e->getCode());
        }
        $this->assertCount(1, $this->serviceRequests());
        $this->assertCount(1, $this->http->requestsTo('/token'));
    }

    public function testLookupErrorObjects()
    {
        $this->configureMock()->append(self::voiceInToken(), self::pricesJson(400, '{"errors":[{"title":"Bad","detail":"number is invalid","code":"100"},"junk",{"title":"Also bad"}]}'));

        try {
            Prices::voiceIn()->lookup(['number' => '18005550123']);
            $this->fail('expected an exception');
        } catch (DidwwPricesException $e) {
            $this->assertSame([['title' => 'Bad', 'detail' => 'number is invalid', 'code' => '100'], ['title' => 'Also bad']], $e->getErrors());
            $this->assertSame('Prices service: number is invalid; Also bad', $e->getMessage());
        }
    }

    public function testLookupDoesNotFollowRedirects()
    {
        $this->configureMock()->append(self::voiceInToken(), new Response(302, ['Location' => '/v3/elsewhere']));

        try {
            Prices::voiceIn()->lookup(['number' => '18005550123']);
            $this->fail('expected an exception');
        } catch (DidwwPricesException $e) {
            $this->assertSame(PricesErrorKind::UNEXPECTED_STATUS, $e->getKind());
            $this->assertSame(302, $e->getStatusCode());
        }
        $this->assertCount(1, $this->serviceRequests());
        $this->assertFalse($this->serviceOptions()[0]['allow_redirects']);
    }

    public function testLookupRetriesRefusedTokenOnce()
    {
        $this->configureMock()->append(
            self::voiceInToken('token-1'),
            self::unauthorizedPrices(),
            self::voiceInToken('token-2'),
            self::pricesJson(200, self::fixture('voice_in/lookup/200.json')),
        );

        $result = Prices::voiceIn()->lookup(['number' => '18005550123']);

        $this->assertSame(2, $result->getTotalRecords());
        $requests = $this->serviceRequests();
        $this->assertCount(2, $requests);
        $this->assertSame('Bearer token-1', $requests[0]->getHeaderLine('Authorization'));
        $this->assertSame('Bearer token-2', $requests[1]->getHeaderLine('Authorization'));
        $this->assertSame([Audience::VOICE_IN, Audience::VOICE_IN], $this->mintAudiences());
    }

    public function testLookupNotAuthorizedAfterRetry()
    {
        $this->configureMock()->append(self::voiceInToken('token-1'), self::unauthorizedPrices(), self::voiceInToken('token-2'), self::unauthorizedPrices());

        try {
            Prices::voiceIn()->lookup(['number' => '18005550123']);
            $this->fail('expected an exception');
        } catch (DidwwPricesException $e) {
            $this->assertSame(PricesErrorKind::NOT_AUTHORIZED, $e->getKind());
            $this->assertSame(401, $e->getStatusCode());
            $this->assertSame('401', $e->getErrorCode());
            $this->assertSame('Prices service: Authorization failed', $e->getMessage());
        }
        $this->assertCount(2, $this->serviceRequests());
        $this->assertCount(2, $this->http->requestsTo('/token'));
    }

    public function testLookupConnectionError()
    {
        $failure = new ConnectException('Connection refused', new Request('GET', 'https://api-vin-sandbox.didww.com/v3/lookup/prices'));
        $this->configureMock()->append(self::voiceInToken(), $failure);

        try {
            Prices::voiceIn()->lookup(['number' => '18005550123']);
            $this->fail('expected an exception');
        } catch (DidwwPricesException $e) {
            $this->assertSame(PricesErrorKind::CONNECTION_ERROR, $e->getKind());
            $this->assertNull($e->getStatusCode());
            $this->assertSame($failure, $e->getPrevious());
            $this->assertSame('Prices service: Connection refused', $e->getMessage());
        }
    }

    public function testLookupThrowsTokenErrorAsIs()
    {
        $this->configureMock()->append(MockHttp::tokenError(401, 'invalid_client.json'));

        try {
            Prices::voiceIn()->lookup(['number' => '18005550123']);
            $this->fail('expected an exception');
        } catch (DidwwAuthException $e) {
            $this->assertSame(AuthErrorKind::INVALID_CLIENT, $e->getKind());
        }
        $this->assertSame([], $this->serviceRequests());
    }

    public static function unparsableBodyProvider(): array
    {
        return [
            'HTML' => ['<html>maintenance</html>'],
            'empty object' => ['{}'],
            'null data' => ['{"data":null}'],
            'empty data' => ['{"data":[],"meta":{"total_records":0}}'],
            'data object' => ['{"data":{"id":"x","type":"prices","attributes":{}}}'],
            'row not an object' => ['{"data":["x"]}'],
            'row without attributes' => ['{"data":[{"id":"x","type":"prices"}]}'],
            'attribute of the wrong type' => ['{"data":[{"id":"x","type":"prices","attributes":{"category":"Local","prefix":4930}}]}'],
            'meta not an object' => [str_replace('"meta": {', '"meta": 1, "x": {', self::fixture('voice_out/lookup/200.json'))],
        ];
    }

    #[DataProvider('unparsableBodyProvider')]
    public function testLookupUnparsableBody(string $body)
    {
        $this->configureMock()->append(self::tokenFor(Audience::VOICE_OUT), self::pricesJson(200, $body));

        try {
            Prices::voiceOut()->lookup(['number' => '442071234567']);
            $this->fail('expected an exception');
        } catch (\UnexpectedValueException $e) {
            $this->assertStringStartsWith('Failed to parse price lookup', $e->getMessage());
        }
        $this->assertCount(1, $this->serviceRequests());
    }

    public function testLookupTimeoutKeepsShorterClientTimeout()
    {
        $this->configureMock(['timeout' => 20])->append(self::voiceInToken(), self::pricesJson(200, self::fixture('voice_in/lookup/200.json')));

        Prices::voiceIn()->lookup(['number' => '18005550123']);

        $this->assertEquals(20, $this->serviceOptions()[0]['timeout']);
    }

    public function testLookupUsesConfiguredUrl()
    {
        $this->configureMock([], null, voiceInPricesUrl: 'https://prices.example.com/base/')
            ->append(self::voiceInToken(), self::pricesJson(200, self::fixture('voice_in/lookup/200.json')));

        Prices::voiceIn()->lookup(['number' => '18005550123']);

        $this->assertSame('https://prices.example.com/base/v3/lookup/prices', (string) $this->serviceRequests()[0]->getUri()->withQuery(''));
    }

    public function testServicesCallTheirOwnHost()
    {
        $this->configureMock(
            [],
            null,
            voiceInPricesUrl: 'https://vin.example.com',
            voiceOutPricesUrl: 'https://vout.example.com/v3',
            smsPricesUrl: 'https://sms.example.com',
        )->append(
            self::tokenFor(Audience::VOICE_IN), new Response(200),
            self::tokenFor(Audience::VOICE_OUT), new Response(200),
            self::tokenFor(Audience::SMS), new Response(200), new Response(200), new Response(200), new Response(200),
        );

        foreach (['voiceIn', 'voiceOut', 'smsOutA2p', 'smsOutP2p', 'smsOutOtp', 'smsIn'] as $service) {
            Prices::$service()->head();
        }

        $this->assertSame([
            'https://vin.example.com/v3/prices.csv.gz',
            'https://vout.example.com/v3/prices.csv.gz',
            'https://sms.example.com/v3/sms_out/a2p/prices.csv.gz',
            'https://sms.example.com/v3/sms_out/p2p/prices.csv.gz',
            'https://sms.example.com/v3/sms_out/otp/prices.csv.gz',
            'https://sms.example.com/v3/sms_in/prices.csv.gz',
        ], array_map(static fn ($request) => (string) $request->getUri(), $this->serviceRequests()));
        $this->assertSame([Audience::VOICE_IN, Audience::VOICE_OUT, Audience::SMS], $this->mintAudiences());
    }

    public function testServicePaths()
    {
        $this->assertSame('prices.csv.gz', Prices::voiceIn()->getFilePath());
        $this->assertSame('lookup/prices', Prices::voiceOut()->getLookupPath());
        $this->assertSame('sms_out/a2p/prices.csv.gz', Prices::smsOutA2p()->getFilePath());
        $this->assertSame('sms_in/lookup/prices', Prices::smsIn()->getLookupPath());
        $this->assertSame(['number', 'src_country_iso'], Prices::voiceIn()->getAllowedFilters());
        $this->assertSame(['dst_number', 'dst_country_iso'], Prices::smsOutOtp()->getAllowedFilters());
        $this->assertSame(Audience::SMS, Prices::smsOutP2p()->getAudience());
        $this->assertInstanceOf(PricesService::class, Prices::voiceOut());
    }

    public static function exceptionMessageProvider(): array
    {
        return [
            'detail over title' => [new DidwwPricesException(PricesErrorKind::BAD_REQUEST, 400, null, [['title' => 'Bad', 'detail' => 'number is invalid'], ['title' => 'Also bad']]),
                'Prices service: number is invalid; Also bad'],
            'status fallback' => [new DidwwPricesException(PricesErrorKind::UNEXPECTED_STATUS, 202), 'Prices service responded with status 202'],
            'transport error' => [new DidwwPricesException(PricesErrorKind::CONNECTION_ERROR, previous: new \RuntimeException('connection refused')),
                'Prices service: connection refused'],
        ];
    }

    #[DataProvider('exceptionMessageProvider')]
    public function testExceptionMessage(DidwwPricesException $e, string $message)
    {
        $this->assertSame($message, $e->getMessage());
        $this->assertInstanceOf(\Didww\Exception\DidwwExceptionInterface::class, $e);
    }
}
