<?php

namespace Didww\Item\AuthenticationMethod;

use Didww\Traits\HasIpAttributes;

class CredentialsAndIp extends Base
{
    use HasIpAttributes;

    public function getType(): string
    {
        return 'credentials_and_ip';
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
