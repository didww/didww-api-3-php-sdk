<?php

namespace Didww\Enum;

enum ExportType: string
{
    case CDR_IN = 'cdr_in';
    case CDR_OUT = 'cdr_out';
    case SMS_IN = 'sms_in';
    case SMS_OUT = 'sms_out';
}
