<?php

namespace Didww\Enum;

/**
 * RTCP feedback (AVPF) on the calls of a trunk: not used, accepted when the
 * other side offers it, or initiated by DIDWW. (API 2026-11-19).
 */
enum RtcpFeedbackMode: string
{
    case DISABLED = 'disabled';
    case ACCEPT = 'accept';
    case INITIATE = 'initiate';
}
