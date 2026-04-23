<?php

namespace Didww\Enum;

enum DiversionRelayPolicy: string
{
    case NONE = 'none';
    case AS_IS = 'as_is';
    case SIP = 'sip';
    case TEL = 'tel';
}
