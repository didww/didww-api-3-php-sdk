<?php

namespace Didww\Enum;

enum OrderStatus: string
{
    case PENDING = 'Pending';
    case CANCELED = 'Canceled';
    case COMPLETED = 'Completed';
}
