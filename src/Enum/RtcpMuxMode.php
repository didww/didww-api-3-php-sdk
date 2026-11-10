<?php

namespace Didww\Enum;

/**
 * RTP and RTCP multiplexed on one port on the calls of a trunk: not used,
 * accepted when the other side offers it, or initiated by DIDWW.
 * (API 2026-10-22).
 */
enum RtcpMuxMode: string
{
    case DISABLED = 'disabled';
    case ACCEPT = 'accept';
    case INITIATE = 'initiate';
}
