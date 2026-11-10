<?php

namespace Didww\Enum;

/**
 * ICE (Interactive Connectivity Establishment) on the calls of a trunk: not
 * used, accepted when the other side offers it, or initiated by DIDWW.
 * (API 2026-10-22).
 */
enum IceMode: string
{
    case DISABLED = 'disabled';
    case ACCEPT = 'accept';
    case INITIATE = 'initiate';
}
