<?php

namespace Didww\Item\AuthenticationMethod;

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
