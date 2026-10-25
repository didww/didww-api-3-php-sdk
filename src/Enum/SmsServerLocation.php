<?php

namespace Didww\Enum;

/**
 * Location of the DIDWW SMPP server an SMPP trunk is served from.
 * Production accepts both; the sandbox accepts `nyc` only.
 */
enum SmsServerLocation: string
{
    case NYC = 'nyc';
    case AMS = 'ams';
}
