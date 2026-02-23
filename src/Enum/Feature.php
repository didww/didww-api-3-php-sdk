<?php

namespace Didww\Enum;

enum Feature: string
{
    case VOICE_IN = 'voice_in';
    case VOICE_OUT = 'voice_out';
    case T38 = 't38';
    case SMS_IN = 'sms_in';
    case SMS_OUT = 'sms_out';
}
