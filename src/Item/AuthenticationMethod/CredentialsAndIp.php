<?php

namespace Didww\Item\AuthenticationMethod;

class CredentialsAndIp extends Base
{
    public function getType(): string
    {
        return 'credentials_and_ip';
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

    /** Server-generated, read-only */
    public function getUsername(): ?string
    {
        return $this->attribute('username');
    }

    /** Server-generated, read-only */
    public function getPassword(): ?string
    {
        return $this->attribute('password');
    }
}
