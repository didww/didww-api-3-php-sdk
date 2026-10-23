<?php

namespace Didww;

use Didww\Auth\Audience;
use Didww\Prices\PricesService;
use Didww\Prices\SmsInPrice;
use Didww\Prices\SmsOutPrice;
use Didww\Prices\VoiceInPrice;
use Didww\Prices\VoiceOutPrice;

/**
 * The price lists of the prices services. Each service reads the configuration
 * at the time of a call, so Configuration::configure() must have been called
 * before.
 *
 * ```php
 * $lookup = \Didww\Prices::voiceIn()->lookup(['number' => '18005550123']);
 * \Didww\Prices::smsOutA2p()->fetch('/var/lib/prices/sms_out_a2p.csv.gz');
 * foreach (\Didww\Prices\PriceFileReader::rows('/var/lib/prices/sms_out_a2p.csv.gz') as $row) { ... }
 * ```
 */
final class Prices
{
    private const SMS_FILTERS = ['anyOf' => ['dst_number', 'dst_country_iso']];

    private function __construct()
    {
    }

    /**
     * The Voice IN price list. Lookup filters: number (required), src_country_iso.
     *
     * @return PricesService<VoiceInPrice>
     */
    public static function voiceIn(): PricesService
    {
        return new PricesService(Configuration::getVoiceInPricesUri(...), Audience::VOICE_IN, '', VoiceInPrice::class, ['required' => ['number'], 'optional' => ['src_country_iso']]);
    }

    /**
     * The Voice OUT price list. Lookup filters: number (required).
     *
     * @return PricesService<VoiceOutPrice>
     */
    public static function voiceOut(): PricesService
    {
        return new PricesService(Configuration::getVoiceOutPricesUri(...), Audience::VOICE_OUT, '', VoiceOutPrice::class, ['required' => ['number']]);
    }

    /**
     * The SMS OUT A2P price list. Lookup filters: dst_number, dst_country_iso (at least one).
     *
     * @return PricesService<SmsOutPrice>
     */
    public static function smsOutA2p(): PricesService
    {
        return new PricesService(Configuration::getSmsPricesUri(...), Audience::SMS, 'sms_out/a2p/', SmsOutPrice::class, self::SMS_FILTERS);
    }

    /**
     * The SMS OUT P2P price list. Lookup filters: dst_number, dst_country_iso (at least one).
     *
     * @return PricesService<SmsOutPrice>
     */
    public static function smsOutP2p(): PricesService
    {
        return new PricesService(Configuration::getSmsPricesUri(...), Audience::SMS, 'sms_out/p2p/', SmsOutPrice::class, self::SMS_FILTERS);
    }

    /**
     * The SMS OUT OTP price list. Lookup filters: dst_number, dst_country_iso (at least one).
     *
     * @return PricesService<SmsOutPrice>
     */
    public static function smsOutOtp(): PricesService
    {
        return new PricesService(Configuration::getSmsPricesUri(...), Audience::SMS, 'sms_out/otp/', SmsOutPrice::class, self::SMS_FILTERS);
    }

    /**
     * The SMS IN price list. Lookup filters: dst_number, dst_country_iso (at least one).
     *
     * @return PricesService<SmsInPrice>
     */
    public static function smsIn(): PricesService
    {
        return new PricesService(Configuration::getSmsPricesUri(...), Audience::SMS, 'sms_in/', SmsInPrice::class, self::SMS_FILTERS);
    }
}
