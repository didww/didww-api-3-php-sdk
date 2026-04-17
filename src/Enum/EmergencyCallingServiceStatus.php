<?php

namespace Didww\Enum;

enum EmergencyCallingServiceStatus: string
{
    case ACTIVE = 'active';
    case CANCELED = 'canceled';
    case CHANGES_REQUIRED = 'changes_required';
    case IN_PROCESS = 'in_process';
    case NEW = 'new';
    case PENDING_UPDATE = 'pending_update';
}
