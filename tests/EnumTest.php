<?php

namespace Didww\Tests;

use Didww\Enum\AddressVerificationStatus;
use Didww\Enum\AreaLevel;
use Didww\Enum\CallbackMethod;
use Didww\Enum\CliFormat;
use Didww\Enum\Codec;
use Didww\Enum\DefaultDstAction;
use Didww\Enum\ExportStatus;
use Didww\Enum\ExportType;
use Didww\Enum\Feature;
use Didww\Enum\IdentityType;
use Didww\Enum\MediaEncryptionMode;
use Didww\Enum\OnCliMismatchAction;
use Didww\Enum\OrderStatus;
use Didww\Enum\ReroutingDisconnectCode;
use Didww\Enum\RxDtmfFormat;
use Didww\Enum\SstRefreshMethod;
use Didww\Enum\StirShakenMode;
use Didww\Enum\TransportProtocol;
use Didww\Enum\TxDtmfFormat;
use Didww\Enum\VoiceOutTrunkStatus;

class EnumTest extends \PHPUnit\Framework\TestCase
{
    public function testStringEnumRoundTrip()
    {
        $cases = [
            [CliFormat::class, 'raw', CliFormat::RAW],
            [IdentityType::class, 'Personal', IdentityType::PERSONAL],
            [OrderStatus::class, 'Completed', OrderStatus::COMPLETED],
            [CallbackMethod::class, 'POST', CallbackMethod::POST],
            [ExportType::class, 'cdr_in', ExportType::CDR_IN],
            [ExportStatus::class, 'Pending', ExportStatus::PENDING],
            [AddressVerificationStatus::class, 'Approved', AddressVerificationStatus::APPROVED],
            [MediaEncryptionMode::class, 'zrtp', MediaEncryptionMode::ZRTP],
            [StirShakenMode::class, 'pai', StirShakenMode::PAI],
            [OnCliMismatchAction::class, 'replace_cli', OnCliMismatchAction::REPLACE_CLI],
            [DefaultDstAction::class, 'allow_all', DefaultDstAction::ALLOW_ALL],
            [VoiceOutTrunkStatus::class, 'active', VoiceOutTrunkStatus::ACTIVE],
            [Feature::class, 'voice_in', Feature::VOICE_IN],
            [AreaLevel::class, 'Country', AreaLevel::COUNTRY],
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
            [Codec::class, 9, Codec::PCMU],
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
        $this->assertCount(2, IdentityType::cases());
        $this->assertCount(3, OrderStatus::cases());
        $this->assertCount(2, CallbackMethod::cases());
        $this->assertCount(2, ExportType::cases());
        $this->assertCount(3, ExportStatus::cases());
        $this->assertCount(3, AddressVerificationStatus::cases());
        $this->assertCount(4, MediaEncryptionMode::cases());
        $this->assertCount(5, StirShakenMode::cases());
        $this->assertCount(3, OnCliMismatchAction::cases());
        $this->assertCount(2, DefaultDstAction::cases());
        $this->assertCount(2, VoiceOutTrunkStatus::cases());
        $this->assertCount(5, Feature::cases());
        $this->assertCount(4, AreaLevel::cases());
        $this->assertCount(13, Codec::cases());
        $this->assertCount(3, TransportProtocol::cases());
        $this->assertCount(3, RxDtmfFormat::cases());
        $this->assertCount(4, TxDtmfFormat::cases());
        $this->assertCount(3, SstRefreshMethod::cases());
        $this->assertCount(47, ReroutingDisconnectCode::cases());
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
            'codec_ids' => [Codec::PCMU, Codec::PCMA],
            'sst_refresh_method_id' => SstRefreshMethod::INVITE,
        ]);

        $attrs = $sip->getAttributes();
        $this->assertSame('zrtp', $attrs['media_encryption_mode']);
        $this->assertSame('pai', $attrs['stir_shaken_mode']);
        $this->assertSame([9, 10], $attrs['codec_ids']);
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

    public function testCodecArraySetterAcceptsEnumAndRaw()
    {
        $sip = new \Didww\Item\Configuration\Sip();

        // Test with enum array
        $sip->setCodecIds([Codec::PCMU, Codec::PCMA]);
        $this->assertSame([9, 10], $sip->getAttributes()['codec_ids']);

        // Test with raw int array
        $sip->setCodecIds([7, 8]);
        $this->assertSame([7, 8], $sip->getAttributes()['codec_ids']);

        // Test with mixed array
        $sip->setCodecIds([Codec::G729, 9]);
        $this->assertSame([8, 9], $sip->getAttributes()['codec_ids']);
    }
}
