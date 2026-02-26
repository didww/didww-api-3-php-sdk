<?php

namespace Didww\Enum;

enum AddressVerificationStatus: string
{
    case PENDING = 'Pending';
    case APPROVED = 'Approved';
    case REJECTED = 'Rejected';
}
