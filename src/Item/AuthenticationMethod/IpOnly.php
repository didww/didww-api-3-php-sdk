<?php

namespace Didww\Item\AuthenticationMethod;

use Didww\Traits\HasIpAttributes;

/**
 * Read-only authentication method for Voice Out Trunks.
 *
 * ip_only can only be configured manually by DIDWW staff upon request.
 * It cannot be set via the API on create or update.
 * Trunks with ip_only authentication can still be read and their
 * non-auth attributes (name, external_reference_id, etc.) updated.
 */
class IpOnly extends Base
{
    use HasIpAttributes;

    public function getType(): string
    {
        return 'ip_only';
    }
}
