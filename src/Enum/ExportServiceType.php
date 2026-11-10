<?php

namespace Didww\Enum;

/**
 * Value of the `service_type` filter of an sms_out export. (API 2026-10-22).
 */
enum ExportServiceType: string
{
    case A2P = 'a2p';
    case P2P = 'p2p';
}
