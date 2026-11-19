<?php

namespace Didww\Enum;

enum SenderIdVerificationStatus: string
{
    case ACTIVE = 'active';
    case CHANGES_REQUIRED = 'changes_required';
    case CLOSED = 'closed';
    case CLOSING_IN_PROCESS = 'closing_in_process';
    case IN_PROCESS = 'in_process';
    case NEW = 'new';
}
