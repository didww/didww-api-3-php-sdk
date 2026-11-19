<?php

namespace Didww\Enum;

enum SenderType: string
{
    case ALPHANUMERIC = 'alphanumeric';
    case CARRIER = 'carrier';
    case LONG_CODE = 'long_code';
    case SHORT_CODE = 'short_code';
    case TOLL_FREE = 'toll_free';
}
