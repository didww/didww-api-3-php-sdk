<?php

namespace Didww\Item\Configuration;

use Didww\Enum\Codec;
use Didww\Enum\MediaEncryptionMode;
use Didww\Enum\ReroutingDisconnectCode;
use Didww\Enum\RxDtmfFormat;
use Didww\Enum\SstRefreshMethod;
use Didww\Enum\StirShakenMode;
use Didww\Enum\TransportProtocol;
use Didww\Enum\TxDtmfFormat;

abstract class Base extends \Didww\Item\BaseItem
{
    protected $attributes = [];

    public function __construct($attributes = [])
    {
        $this->fill($attributes);
    }

    public function toJsonApiArray(): array
    {
        return [
            'type' => $this->getType(),
            'attributes' => $this->getAttributes(),
        ];
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function fill(array $attributes)
    {
        $this->attributes = array_map(function ($value) {
            if ($value instanceof \BackedEnum) {
                return $value->value;
            }
            if (is_array($value)) {
                return array_map(fn ($v) => $v instanceof \BackedEnum ? $v->value : $v, $value);
            }

            return $value;
        }, $attributes);

        return $this;
    }

    private static $didPlaceHolder = '{DID}';

    /**
     * @return RxDtmfFormat[]
     */
    public static function getRxDtmfFormats(): array
    {
        return RxDtmfFormat::cases();
    }

    /**
     * @return TxDtmfFormat[]
     */
    public static function getTxDtmfFormats(): array
    {
        return TxDtmfFormat::cases();
    }

    /**
     * @return SstRefreshMethod[]
     */
    public static function getSstRefreshMethods(): array
    {
        return SstRefreshMethod::cases();
    }

    /**
     * @return TransportProtocol[]
     */
    public static function getTransportProtocols(): array
    {
        return TransportProtocol::cases();
    }

    /**
     * @return ReroutingDisconnectCode[]
     */
    public static function getReroutingDisconnectCodes(): array
    {
        return ReroutingDisconnectCode::cases();
    }

    /**
     * @return Codec[]
     */
    public static function getCodecs(): array
    {
        return Codec::cases();
    }

    /**
     * @return ReroutingDisconnectCode[]
     */
    public static function getDefaultReroutingDisconnectCodeIds(): array
    {
        return [
            ReroutingDisconnectCode::SIP_400_BAD_REQUEST,
            ReroutingDisconnectCode::SIP_402_PAYMENT_REQUIRED,
            ReroutingDisconnectCode::SIP_403_FORBIDDEN,
            ReroutingDisconnectCode::SIP_404_NOT_FOUND,
            ReroutingDisconnectCode::SIP_408_REQUEST_TIMEOUT,
            ReroutingDisconnectCode::SIP_409_CONFLICT,
            ReroutingDisconnectCode::SIP_410_GONE,
            ReroutingDisconnectCode::SIP_412_CONDITIONAL_REQUEST_FAILED,
            ReroutingDisconnectCode::SIP_413_REQUEST_ENTITY_TOO_LARGE,
            ReroutingDisconnectCode::SIP_414_REQUEST_URI_TOO_LONG,
            ReroutingDisconnectCode::SIP_415_UNSUPPORTED_MEDIA_TYPE,
            ReroutingDisconnectCode::SIP_416_UNSUPPORTED_URI_SCHEME,
            ReroutingDisconnectCode::SIP_417_UNKNOWN_RESOURCE_PRIORITY,
            ReroutingDisconnectCode::SIP_420_BAD_EXTENSION,
            ReroutingDisconnectCode::SIP_421_EXTENSION_REQUIRED,
            ReroutingDisconnectCode::SIP_422_SESSION_INTERVAL_TOO_SMALL,
            ReroutingDisconnectCode::SIP_423_INTERVAL_TOO_BRIEF,
            ReroutingDisconnectCode::SIP_424_BAD_LOCATION_INFORMATION,
            ReroutingDisconnectCode::SIP_428_USE_IDENTITY_HEADER,
            ReroutingDisconnectCode::SIP_429_PROVIDE_REFERRER_IDENTITY,
            ReroutingDisconnectCode::SIP_433_ANONYMITY_DISALLOWED,
            ReroutingDisconnectCode::SIP_436_BAD_IDENTITY_INFO,
            ReroutingDisconnectCode::SIP_437_UNSUPPORTED_CERTIFICATE,
            ReroutingDisconnectCode::SIP_438_INVALID_IDENTITY_HEADER,
            ReroutingDisconnectCode::SIP_480_TEMPORARILY_UNAVAILABLE,
            ReroutingDisconnectCode::SIP_482_LOOP_DETECTED,
            ReroutingDisconnectCode::SIP_483_TOO_MANY_HOPS,
            ReroutingDisconnectCode::SIP_484_ADDRESS_INCOMPLETE,
            ReroutingDisconnectCode::SIP_485_AMBIGUOUS,
            ReroutingDisconnectCode::SIP_486_BUSY_HERE,
            ReroutingDisconnectCode::SIP_487_REQUEST_TERMINATED,
            ReroutingDisconnectCode::SIP_488_NOT_ACCEPTABLE_HERE,
            ReroutingDisconnectCode::SIP_494_SECURITY_AGREEMENT_REQUIRED,
            ReroutingDisconnectCode::SIP_500_SERVER_INTERNAL_ERROR,
            ReroutingDisconnectCode::SIP_501_NOT_IMPLEMENTED,
            ReroutingDisconnectCode::SIP_502_BAD_GATEWAY,
            ReroutingDisconnectCode::SIP_504_SERVER_TIMEOUT,
            ReroutingDisconnectCode::SIP_505_VERSION_NOT_SUPPORTED,
            ReroutingDisconnectCode::SIP_513_MESSAGE_TOO_LARGE,
            ReroutingDisconnectCode::SIP_580_PRECONDITION_FAILURE,
            ReroutingDisconnectCode::SIP_600_BUSY_EVERYWHERE,
            ReroutingDisconnectCode::SIP_603_DECLINE,
            ReroutingDisconnectCode::SIP_604_DOES_NOT_EXIST_ANYWHERE,
            ReroutingDisconnectCode::SIP_606_NOT_ACCEPTABLE,
            ReroutingDisconnectCode::RINGING_TIMEOUT,
        ];
    }

    /**
     * @return Codec[]
     */
    public static function getDefaultCodecIds(): array
    {
        return [
            Codec::PCMU,
            Codec::PCMA,
            Codec::G729,
            Codec::G723,
            Codec::TELEPHONE_EVENT,
        ];
    }

    /**
     * @return MediaEncryptionMode[]
     */
    public static function getMediaEncryptionModes(): array
    {
        return MediaEncryptionMode::cases();
    }

    /**
     * @return StirShakenMode[]
     */
    public static function getStirShakenModes(): array
    {
        return StirShakenMode::cases();
    }

    public static function getDidPlaceHolder()
    {
        return self::$didPlaceHolder;
    }
}
