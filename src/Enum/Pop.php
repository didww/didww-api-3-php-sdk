<?php

namespace Didww\Enum;

/**
 * Point of presence a voice IN trunk receives calls through.
 */
enum Pop: string
{
    case NYC = 'NYC';
    case FRA = 'FRA';
    case LAC = 'LAC';
    case MIA = 'MIA';
    case SG = 'SG';
    case HK = 'HK';
    case AMS = 'AMS';
}
