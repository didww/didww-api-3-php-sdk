<?php

namespace Didww\Traits;

/**
 * Shared IP-based authentication attributes for IpOnly and CredentialsAndIp methods.
 */
trait HasIpAttributes
{
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
