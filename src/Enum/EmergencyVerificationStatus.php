<?php

namespace Didww\Enum;

enum EmergencyVerificationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
