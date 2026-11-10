<?php

namespace Didww\Enum;

/**
 * Point of presence a voice IN trunk receives calls through.
 */
enum Pop: string
{
    case NYC = 'nyc';
    case FRA = 'fra';
    case LAC = 'lac';
    case MIA = 'mia';
    case SG = 'sg';
    case HK = 'hk';
    case AMS = 'ams';
}
