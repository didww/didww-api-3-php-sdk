<?php

namespace Didww\Enum;

enum NumberType: string
{
    case LOCAL = 'local';
    case NATIONAL = 'national';
    case MOBILE = 'mobile';
    case TOLL_FREE = 'toll_free';
    case SHARED_COST = 'shared_cost';
    case GLOBAL = 'global';
}
