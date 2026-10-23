<?php

namespace Didww\Enum;

enum ExportTrunkType: string
{
    case SMPP_SMSC = 'SMPP SMSC';
    case SMPP_ESME = 'SMPP ESME';
    case EMAIL = 'Email';
    case HTTP_IN = 'HTTP IN';
}
