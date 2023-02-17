<?php

namespace Didww\Item\Configuration;

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
        $this->attributes = $attributes;

        return $this;
    }

    private static $didPlaceHolder = '{DID}';
    private static $options = [
     'rx_dtmf_formats' => [
       1 => 'RFC 2833',
       2 => 'SIP INFO application/dtmf-relay OR application/dtmf',
       3 => 'RFC 2833 OR SIP INFO',
     ],
     'tx_dtmf_formats' => [
        0 => 'Disable sending',
        1 => 'RFC 2833',
        2 => 'SIP INFO application/dtmf-relay',
        4 => 'SIP INFO application/dtmf',
     ],
     'sst_refresh_methods' => [
           1 => 'Invite',
           2 => 'Update',
           3 => 'Update fallback Invite',
      ],
      'transport_protocols' => [
            1 => 'UDP',
            2 => 'TCP',
            3 => 'TLS',
       ],
       'rerouting_disconnect_codes' => [
            56 => '400 | Bad Request',
            57 => '401 | Unauthorized',
            58 => '402 | Payment Required',
            59 => '403 | Forbidden',
            60 => '404 | Not Found',
            64 => '408 | Request Timeout',
            65 => '409 | Conflict',
            66 => '410 | Gone',
            67 => '412 | Conditional Request Failed',
            68 => '413 | Request Entity Too Large',
            69 => '414 | Request-URI Too Long',
            70 => '415 | Unsupported Media Type',
            71 => '416 | Unsupported URI Scheme',
            72 => '417 | Unknown Resource-Priority',
            73 => '420 | Bad Extension',
            74 => '421 | Extension Required',
            75 => '422 | Session Interval Too Small',
            76 => '423 | Interval Too Brief',
            77 => '424 | Bad Location Information',
            78 => '428 | Use Identity Header',
            79 => '429 | Provide Referrer Identity',
            80 => '433 | Anonymity Disallowed',
            81 => '436 | Bad Identity-Info',
            82 => '437 | Unsupported Certificate',
            83 => '438 | Invalid Identity Header',
            84 => '480 | Temporarily Unavailable',
            86 => '482 | Loop Detected',
            87 => '483 | Too Many Hops',
            88 => '484 | Address Incomplete',
            89 => '485 | Ambiguous',
            90 => '486 | Busy Here',
            91 => '487 | Request Terminated',
            92 => '488 | Not Acceptable Here',
            96 => '494 | Security Agreement Required',
            97 => '500 | Server Internal Error',
            98 => '501 | Not Implemented',
            99 => '502 | Bad Gateway',
            100 => '503 | Service Unavailable',
            101 => '504 | Server Time-out',
            102 => '505 | Version Not Supported',
            103 => '513 | Message Too Large',
            104 => '580 | Precondition Failure',
            105 => '600 | Busy Everywhere',
            106 => '603 | Decline',
            107 => '604 | Does Not Exist Anywhere',
            108 => '606 | Not Acceptable',
            1505 => 'Ringing timeout',
       ],

       'codecs' => [
         6 => 'telephone-event',
          7 => 'G723',
          8 => 'G729',
          9 => 'PCMU',
          10 => 'PCMA',
          12 => 'speex',
          13 => 'GSM',
          14 => 'G726-32',
          15 => 'G721',
          16 => 'G726-24',
          17 => 'G726-40',
          18 => 'G726-16',
          19 => 'L16',
        ],
       'default_rerouting_disconnect_code_ids' => [
         56,
         58,
         59,
         60,
         64,
         65,
         66,
         67,
         68,
         69,
         70,
         71,
         72,
         73,
         74,
         75,
         76,
         77,
         78,
         79,
         80,
         81,
         82,
         83,
         84,
         86,
         87,
         88,
         89,
         90,
         91,
         92,
         96,
         97,
         98,
         99,
         101,
         102,
         103,
         104,
         105,
         106,
         107,
         108,
         1505,
       ],
       'default_codec_ids' => [9, 10, 8, 7, 6],
       'media_encryption_mode' => [
         'disabled',
         'srtp_sdes',
         'srtp_dtls',
         'zrtp',
       ],
       'stir_shaken_mode' => [
         'disabled',
         'original',
         'pai',
         'original_pai',
         'verstat',
       ],
     ];

    public static function getRxDtmfFormats()
    {
        return self::optionsFor('rx_dtmf_formats');
    }

    public static function getTxDtmfFormats()
    {
        return self::optionsFor('tx_dtmf_formats');
    }

    public static function getSstRefreshMethods()
    {
        return self::optionsFor('sst_refresh_methods');
    }

    public static function getTransportProtocols()
    {
        return self::optionsFor('transport_protocols');
    }

    public static function getReroutingDisconnectCodes()
    {
        return self::optionsFor('rerouting_disconnect_codes');
    }

    public static function getCodecs()
    {
        return self::optionsFor('codecs');
    }

    public static function getDefaultReroutingDisconnectCodeIds()
    {
        return self::optionsFor('default_rerouting_disconnect_code_ids');
    }

    public static function getDefaultCodecIds()
    {
        return self::optionsFor('default_codec_ids');
    }

    public static function getMediaEncryptionModes()
    {
        return self::optionsFor('media_encryption_mode');
    }

    public static function getStirShakenModes()
    {
        return self::optionsFor('stir_shaken_mode');
    }

    public static function getDidPlaceHolder()
    {
        return self::$didPlaceHolder;
    }

    private static function optionsFor($section)
    {
        return self::$options[$section];
    }
}
