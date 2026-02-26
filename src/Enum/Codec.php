<?php

namespace Didww\Enum;

enum Codec: int
{
    case TELEPHONE_EVENT = 6;
    case G723 = 7;
    case G729 = 8;
    case PCMU = 9;
    case PCMA = 10;
    case SPEEX = 12;
    case GSM = 13;
    case G726_32 = 14;
    case G721 = 15;
    case G726_24 = 16;
    case G726_40 = 17;
    case G726_16 = 18;
    case L16 = 19;
}
