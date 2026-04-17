<?php

namespace Didww\Enum;

enum EmergencyCallingServiceStatus: string
{
    case ACTIVE = 'active';
    case CANCELED = 'canceled';
    case CHANGES_REQUIRED = 'changes required';
    case IN_PROCESS = 'in process';
    case NEW = 'new';
    case PENDING_UPDATE = 'pending update';
}
