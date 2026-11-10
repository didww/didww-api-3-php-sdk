<?php

namespace Didww\Enum;

/**
 * Codec of an inbound SIP trunk (Configuration\Sip::setCodecs()). A Voice OUT
 * trunk has its own list, VoiceOutCodec. (API 2026-10-22).
 */
enum Codec: string
{
    case TELEPHONE_EVENT = 'telephone_event';
    case G723 = 'g723';
    case G729 = 'g729';
    case PCMU = 'pcmu';
    case PCMA = 'pcma';
    case SPEEX = 'speex';
    case GSM = 'gsm';
    case G726_32 = 'g726_32';
    case G721 = 'g721';
    case G726_24 = 'g726_24';
    case G726_40 = 'g726_40';
    case G726_16 = 'g726_16';
    case L16 = 'l16';
    case AMR = 'amr';
    case AMR_WB = 'amr_wb';
}
