<?php

namespace Didww\Enum;

enum MediaEncryptionMode: string
{
    case DISABLED = 'disabled';
    case SRTP_SDES = 'srtp_sdes';
    case SRTP_DTLS = 'srtp_dtls';
    case ZRTP = 'zrtp';
}
