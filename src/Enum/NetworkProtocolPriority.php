<?php

namespace Didww\Enum;

/**
 * SIP network protocol priority for VoiceInTrunk SIP configuration.
 * (API 2026-04-16).
 */
enum NetworkProtocolPriority: string
{
    case FORCE_IPV4 = 'force_ipv4';
    case FORCE_IPV6 = 'force_ipv6';
    case ANY = 'any';
    case PREFER_IPV4 = 'prefer_ipv4';
    case PREFER_IPV6 = 'prefer_ipv6';
}
