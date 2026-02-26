<?php

namespace Didww\Enum;

enum TxDtmfFormat: int
{
    case DISABLED = 0;
    case RFC_2833 = 1;
    case SIP_INFO_RELAY = 2;
    case SIP_INFO_DTMF = 4;
}
