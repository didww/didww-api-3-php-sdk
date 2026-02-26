<?php

namespace Didww\Enum;

enum CliFormat: string
{
    case RAW = 'raw';
    case E164 = 'e164';
    case LOCAL = 'local';
}
