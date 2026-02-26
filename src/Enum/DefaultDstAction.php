<?php

namespace Didww\Enum;

enum DefaultDstAction: string
{
    case ALLOW_ALL = 'allow_all';
    case REJECT_ALL = 'reject_all';
}
