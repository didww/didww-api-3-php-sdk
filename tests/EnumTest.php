<?php

namespace Didww\Tests;

use Didww\Enum\AddressVerificationStatus;
use Didww\Enum\AreaLevel;
use Didww\Enum\CallbackMethod;
use Didww\Enum\CliFormat;
use Didww\Enum\Codec;
use Didww\Enum\DefaultDstAction;
use Didww\Enum\DiversionRelayPolicy;
use Didww\Enum\EmergencyCallingServiceStatus;
use Didww\Enum\EmergencyVerificationStatus;
use Didww\Enum\ExportServiceType;
use Didww\Enum\ExportStatus;
use Didww\Enum\ExportTrunkType;
use Didww\Enum\ExportType;
use Didww\Enum\Feature;
use Didww\Enum\IceMode;
use Didww\Enum\IdentityType;
use Didww\Enum\MediaEncryptionMode;
use Didww\Enum\NumberType;
use Didww\Enum\OnCliMismatchAction;
use Didww\Enum\OrderStatus;
use Didww\Enum\Pop;
use Didww\Enum\ReroutingDisconnectCode;
use Didww\Enum\RtcpFeedbackMode;
use Didww\Enum\RtcpMuxMode;
use Didww\Enum\RxDtmfFormat;
use Didww\Enum\SenderIdVerificationStatus;
use Didww\Enum\SenderType;
use Didww\Enum\SmsBodyType;
use Didww\Enum\SmsHttpMethod;
use Didww\Enum\SmsServerLocation;
use Didww\Enum\SstRefreshMethod;
use Didww\Enum\StirShakenMode;
use Didww\Enum\TransportProtocol;
use Didww\Enum\TxDtmfFormat;
use Didww\Enum\VoiceOutCodec;
use Didww\Enum\VoiceOutTrunkStatus;

class EnumTest extends \PHPUnit\Framework\TestCase
{
    public function testStringEnumRoundTrip()
    {
        $cases = [
            [CliFormat::class, 'raw', CliFormat::RAW],
            [IdentityType::class, 'personal', IdentityType::PERSONAL],
            [IdentityType::class, 'any', IdentityType::ANY],
            [OrderStatus::class, 'completed', OrderStatus::COMPLETED],
            [CallbackMethod::class, 'post', CallbackMethod::POST],
            [ExportType::class, 'cdr_in', ExportType::CDR_IN],
            [ExportStatus::class, 'pending', ExportStatus::PENDING],
            [AddressVerificationStatus::class, 'approved', AddressVerificationStatus::APPROVED],
            [MediaEncryptionMode::class, 'zrtp', MediaEncryptionMode::ZRTP],
            [StirShakenMode::class, 'pai', StirShakenMode::PAI],
            [OnCliMismatchAction::class, 'replace_cli', OnCliMismatchAction::REPLACE_CLI],
            [DefaultDstAction::class, 'allow_all', DefaultDstAction::ALLOW_ALL],
            [VoiceOutTrunkStatus::class, 'active', VoiceOutTrunkStatus::ACTIVE],
            [Feature::class, 'voice_in', Feature::VOICE_IN],
            [AreaLevel::class, 'country', AreaLevel::COUNTRY],
            [ExportType::class, 'sms_in', ExportType::SMS_IN],
            [ExportType::class, 'sms_out', ExportType::SMS_OUT],
            [ExportTrunkType::class, 'smpp_smsc', ExportTrunkType::SMPP_SMSC],
            [ExportTrunkType::class, 'sms_to_email', ExportTrunkType::SMS_TO_EMAIL],
            [ExportTrunkType::class, 'http_in', ExportTrunkType::HTTP_IN],
            [ExportServiceType::class, 'a2p', ExportServiceType::A2P],
            [NumberType::class, 'toll_free', NumberType::TOLL_FREE],
            [Pop::class, 'fra', Pop::FRA],
            [Codec::class, 'pcmu', Codec::PCMU],
            [Codec::class, 'amr_wb', Codec::AMR_WB],
            [VoiceOutCodec::class, 'opus', VoiceOutCodec::OPUS],
            [IceMode::class, 'accept', IceMode::ACCEPT],
            [RtcpMuxMode::class, 'initiate', RtcpMuxMode::INITIATE],
            [RtcpFeedbackMode::class, 'disabled', RtcpFeedbackMode::DISABLED],
            [SmsHttpMethod::class, 'put', SmsHttpMethod::PUT],
            [SmsBodyType::class, 'html_url_encoded', SmsBodyType::HTML_URL_ENCODED],
            [SmsServerLocation::class, 'ams', SmsServerLocation::AMS],
            [SenderIdVerificationStatus::class, 'closing_in_process', SenderIdVerificationStatus::CLOSING_IN_PROCESS],
            [SenderType::class, 'long_code', SenderType::LONG_CODE],
        ];

        foreach ($cases as [$enumClass, $rawValue, $expectedCase]) {
            $enum = $enumClass::from($rawValue);
            $this->assertSame($expectedCase, $enum);
            $this->assertSame($rawValue, $enum->value);
        }
    }

    public function testIntEnumRoundTrip()
    {
        $cases = [
            [TransportProtocol::class, 1, TransportProtocol::UDP],
            [RxDtmfFormat::class, 1, RxDtmfFormat::RFC_2833],
            [TxDtmfFormat::class, 0, TxDtmfFormat::DISABLED],
            [SstRefreshMethod::class, 1, SstRefreshMethod::INVITE],
            [ReroutingDisconnectCode::class, 56, ReroutingDisconnectCode::SIP_400_BAD_REQUEST],
            [ReroutingDisconnectCode::class, 1505, ReroutingDisconnectCode::RINGING_TIMEOUT],
        ];

        foreach ($cases as [$enumClass, $rawValue, $expectedCase]) {
            $enum = $enumClass::from($rawValue);
            $this->assertSame($expectedCase, $enum);
            $this->assertSame($rawValue, $enum->value);
        }
    }

    public function testEnumCaseCounts()
    {
        $this->assertCount(3, CliFormat::cases());
        $this->assertCount(3, IdentityType::cases());
        $this->assertCount(3, OrderStatus::cases());
        $this->assertCount(2, CallbackMethod::cases());
        $this->assertCount(4, ExportType::cases());
        $this->assertCount(4, ExportTrunkType::cases());
        $this->assertCount(6, NumberType::cases());
        $this->assertCount(7, Pop::cases());
        $this->assertCount(3, SmsHttpMethod::cases());
        $this->assertCount(4, SmsBodyType::cases());
        $this->assertCount(2, SmsServerLocation::cases());
        $this->assertCount(6, SenderIdVerificationStatus::cases());
        $this->assertCount(5, SenderType::cases());
        $this->assertCount(3, ExportStatus::cases());
        $this->assertCount(3, AddressVerificationStatus::cases());
        $this->assertCount(4, MediaEncryptionMode::cases());
        $this->assertCount(5, StirShakenMode::cases());
        $this->assertCount(4, OnCliMismatchAction::cases());
        $this->assertCount(2, DefaultDstAction::cases());
        $this->assertCount(2, VoiceOutTrunkStatus::cases());
        $this->assertCount(8, Feature::cases());
        $this->assertCount(4, AreaLevel::cases());
        $this->assertCount(2, ExportServiceType::cases());
        $this->assertCount(3, TransportProtocol::cases());
        $this->assertCount(3, RxDtmfFormat::cases());
        $this->assertCount(4, TxDtmfFormat::cases());
        $this->assertCount(3, SstRefreshMethod::cases());
        $this->assertCount(47, ReroutingDisconnectCode::cases());
        $this->assertCount(4, DiversionRelayPolicy::cases());
        $this->assertCount(6, EmergencyCallingServiceStatus::cases());
        $this->assertCount(3, EmergencyVerificationStatus::cases());
    }

    public function testValuesFollowTheApi()
    {
        $values = static fn (string $enumClass): array => array_map(static fn (\BackedEnum $case) => $case->value, $enumClass::cases());

        $this->assertSame(['telephone_event', 'g723', 'g729', 'pcmu', 'pcma', 'speex', 'gsm', 'g726_32', 'g721', 'g726_24', 'g726_40', 'g726_16', 'l16', 'amr', 'amr_wb'], $values(Codec::class));
        $this->assertSame(['telephone_event', 'g723', 'g729', 'pcmu', 'pcma', 'gsm', 'g722', 'opus', 'amr', 'amr_wb'], $values(VoiceOutCodec::class));
        foreach ([IceMode::class, RtcpMuxMode::class, RtcpFeedbackMode::class] as $mode) {
            $this->assertSame(['disabled', 'accept', 'initiate'], $values($mode), $mode);
        }
        $this->assertSame(['nyc', 'fra', 'lac', 'mia', 'sg', 'hk', 'ams'], $values(Pop::class));
        $this->assertSame(['smpp_smsc', 'smpp_esme', 'sms_to_email', 'http_in'], $values(ExportTrunkType::class));
        $this->assertSame(['a2p', 'p2p'], $values(ExportServiceType::class));
    }

    public function testSipConfigurationSetterAcceptsEnumAndRaw()
    {
        $sip = new \Didww\Item\Configuration\Sip();

        // Test with enum
        $sip->setMediaEncryptionMode(MediaEncryptionMode::ZRTP);
        $this->assertSame(MediaEncryptionMode::ZRTP, $sip->getMediaEncryptionMode());
        $this->assertSame('zrtp', $sip->getAttributes()['media_encryption_mode']);

        // Test with raw string
        $sip->setMediaEncryptionMode('srtp_sdes');
        $this->assertSame(MediaEncryptionMode::SRTP_SDES, $sip->getMediaEncryptionMode());
        $this->assertSame('srtp_sdes', $sip->getAttributes()['media_encryption_mode']);

        // Test int enum setter with enum
        $sip->setSstRefreshMethodId(SstRefreshMethod::UPDATE);
        $this->assertSame(SstRefreshMethod::UPDATE, $sip->getSstRefreshMethodId());
        $this->assertSame(2, $sip->getAttributes()['sst_refresh_method_id']);

        // Test int enum setter with raw int
        $sip->setSstRefreshMethodId(3);
        $this->assertSame(SstRefreshMethod::UPDATE_FALLBACK_INVITE, $sip->getSstRefreshMethodId());
        $this->assertSame(3, $sip->getAttributes()['sst_refresh_method_id']);
    }

    public function testSipConfigurationFillConvertsEnumsToRaw()
    {
        $sip = new \Didww\Item\Configuration\Sip([
            'media_encryption_mode' => MediaEncryptionMode::ZRTP,
            'stir_shaken_mode' => StirShakenMode::PAI,
            'codecs' => [Codec::PCMU, Codec::PCMA],
            'ice_mode' => IceMode::ACCEPT,
            'sst_refresh_method_id' => SstRefreshMethod::INVITE,
        ]);

        $attrs = $sip->getAttributes();
        $this->assertSame('zrtp', $attrs['media_encryption_mode']);
        $this->assertSame('pai', $attrs['stir_shaken_mode']);
        $this->assertSame(['pcmu', 'pcma'], $attrs['codecs']);
        $this->assertSame('accept', $attrs['ice_mode']);
        $this->assertSame(1, $attrs['sst_refresh_method_id']);
    }

    public function testVoiceOutTrunkSetterAcceptsEnumAndRaw()
    {
        $trunk = new \Didww\Item\VoiceOutTrunk();

        $trunk->setMediaEncryptionMode(MediaEncryptionMode::DISABLED);
        $this->assertSame('disabled', $trunk->getAttributes()['media_encryption_mode']);

        $trunk->setMediaEncryptionMode('srtp_sdes');
        $this->assertSame('srtp_sdes', $trunk->getAttributes()['media_encryption_mode']);

        $trunk->setOnCliMismatchAction(OnCliMismatchAction::REPLACE_CLI);
        $this->assertSame('replace_cli', $trunk->getAttributes()['on_cli_mismatch_action']);

        $trunk->setOnCliMismatchAction('reject_call');
        $this->assertSame('reject_call', $trunk->getAttributes()['on_cli_mismatch_action']);
    }

    public function testSipConfigurationDiversionRelayPolicy()
    {
        $sip = new \Didww\Item\Configuration\Sip();

        $sip->setDiversionRelayPolicy('sip');
        $this->assertSame(DiversionRelayPolicy::SIP, $sip->getDiversionRelayPolicy());
        $this->assertSame('sip', $sip->getAttributes()['diversion_relay_policy']);

        $sip->setDiversionRelayPolicy(DiversionRelayPolicy::AS_IS);
        $this->assertSame(DiversionRelayPolicy::AS_IS, $sip->getDiversionRelayPolicy());
        $this->assertSame('as_is', $sip->getAttributes()['diversion_relay_policy']);
    }

    public function testUnknownEnumValueFallsBackToRawString()
    {
        // Single enum attribute: unknown value should return raw string, not crash
        $didGroup = new \Didww\Item\DidGroup();
        $didGroup->fill(['features' => ['voice_in', 'future_feature_xyz']]);
        $features = $didGroup->getFeatures();
        $this->assertSame(Feature::VOICE_IN, $features[0]);
        $this->assertSame('future_feature_xyz', $features[1]);
    }

    public function testCodecArraySetterAcceptsEnumAndRaw()
    {
        $sip = new \Didww\Item\Configuration\Sip();

        $sip->setCodecs([Codec::PCMA, Codec::AMR_WB]);
        $this->assertSame(['pcma', 'amr_wb'], $sip->getAttributes()['codecs']);

        $sip->setCodecs(['g729', Codec::PCMU, 'future_codec']);
        $this->assertSame(['g729', 'pcmu', 'future_codec'], $sip->getAttributes()['codecs']);
        $this->assertSame([Codec::G729, Codec::PCMU, 'future_codec'], $sip->getCodecs());

        $sip->setCodecs([1 => Codec::PCMA, 3 => 'g729']);
        $this->assertSame(['pcma', 'g729'], $sip->getAttributes()['codecs']);

        $trunk = new \Didww\Item\VoiceOutTrunk();
        $trunk->setCodecs([1 => VoiceOutCodec::OPUS, 3 => 'g722']);
        $this->assertSame(['opus', 'g722'], $trunk->getAttributes()['codecs']);
        $this->assertSame([VoiceOutCodec::OPUS, VoiceOutCodec::G722], $trunk->getCodecs());
    }
}
