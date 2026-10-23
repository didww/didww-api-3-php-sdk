<?php

namespace Didww\Auth;

/**
 * Token audiences: the service hostnames a token is minted for. They do not
 * change with the environment or with overridden service URLs.
 */
final class Audience
{
    /** JSON:API, export download and encrypted file upload. */
    public const API = 'api.didww.com';
    public const VOICE_IN = 'api-vin.didww.com';
    public const VOICE_OUT = 'api-vout.didww.com';
    public const SMS = 'api-sms.didww.com';

    private function __construct()
    {
    }
}
