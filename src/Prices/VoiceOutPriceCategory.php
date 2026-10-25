<?php

namespace Didww\Prices;

/**
 * Category of a Voice OUT price, as the sheets of the price list group them.
 */
enum VoiceOutPriceCategory: string
{
    case INTERNATIONAL = 'International';
    case ORIGIN_BASED = 'Origin based';
    case LOCAL = 'Local';
}
