<?php

namespace Didww\Item\AuthenticationMethod;

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
    public function getType(): string
    {
        return 'ip_only';
    }

    public function getAllowedSipIps(): ?array
    {
        return $this->attribute('allowed_sip_ips');
    }

    public function setAllowedSipIps(array $allowedSipIps): void
    {
        $this->attributes['allowed_sip_ips'] = $allowedSipIps;
    }

    public function getTechPrefix(): ?string
    {
        return $this->attribute('tech_prefix');
    }

    public function setTechPrefix(?string $techPrefix): void
    {
        $this->attributes['tech_prefix'] = $techPrefix;
    }
}
