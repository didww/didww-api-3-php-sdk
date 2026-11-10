<?php

namespace Didww\Enum;

/**
 * Codec of a Voice OUT trunk (VoiceOutTrunk::setCodecs()). An inbound SIP
 * trunk has its own list, Codec. (API 2026-10-22).
 */
enum VoiceOutCodec: string
{
    case TELEPHONE_EVENT = 'telephone_event';
    case G723 = 'g723';
    case G729 = 'g729';
    case PCMU = 'pcmu';
    case PCMA = 'pcma';
    case GSM = 'gsm';
    case G722 = 'g722';
    case OPUS = 'opus';
    case AMR = 'amr';
    case AMR_WB = 'amr_wb';
}
