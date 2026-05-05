<?php

namespace Didww\Enum;

/**
 * SIP diversion header injection mode for VoiceInTrunk SIP configuration.
 * (API 2026-04-16).
 */
enum DiversionInjectMode: string
{
    case NONE = 'none';
    case DID_NUMBER = 'did_number';
}
