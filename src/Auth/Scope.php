<?php

namespace Didww\Auth;

/**
 * The scope requested for access tokens. Without a scope the token service
 * grants full.
 */
enum Scope: string
{
    case READ = 'read';
    case FULL = 'full';
}
