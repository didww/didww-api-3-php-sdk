<?php

namespace Didww\Enum;

/**
 * Value of the `trunk_type` filter of an sms_in export.
 */
enum ExportTrunkType: string
{
    case SMPP_SMSC = 'smpp_smsc';
    case SMPP_ESME = 'smpp_esme';
    case SMS_TO_EMAIL = 'sms_to_email';
    case HTTP_IN = 'http_in';
}
