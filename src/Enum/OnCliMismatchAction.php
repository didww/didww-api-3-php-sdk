<?php

namespace Didww\Enum;

enum OnCliMismatchAction: string
{
    case SEND_ORIGINAL_CLI = 'send_original_cli';
    case REJECT_CALL = 'reject_call';
    case REPLACE_CLI = 'replace_cli';
}
