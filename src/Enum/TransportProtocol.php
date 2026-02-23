<?php

namespace Didww\Enum;

enum TransportProtocol: int
{
    case UDP = 1;
    case TCP = 2;
    case TLS = 3;
}
