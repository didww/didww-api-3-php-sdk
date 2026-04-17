<?php

namespace Didww\Enum;

enum Feature: string
{
    case VOICE_IN = 'voice_in';
    case VOICE_OUT = 'voice_out';
    case T38 = 't38';
    case SMS_IN = 'sms_in';
    case P2P = 'p2p';
    case A2P = 'a2p';
    case EMERGENCY = 'emergency';
    case CNAM_OUT = 'cnam_out';
}
