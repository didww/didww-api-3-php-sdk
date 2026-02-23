<?php

namespace Didww\Enum;

enum SstRefreshMethod: int
{
    case INVITE = 1;
    case UPDATE = 2;
    case UPDATE_FALLBACK_INVITE = 3;
}
