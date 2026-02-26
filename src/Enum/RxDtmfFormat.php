<?php

namespace Didww\Enum;

enum RxDtmfFormat: int
{
    case RFC_2833 = 1;
    case SIP_INFO = 2;
    case RFC_2833_OR_SIP_INFO = 3;
}
