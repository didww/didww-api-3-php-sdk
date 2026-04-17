<?php

namespace Didww\Enum;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CANCELED = 'canceled';
    case COMPLETED = 'completed';
}
