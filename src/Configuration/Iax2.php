<?php

namespace Didww\Configuration;

class Iax2 extends Base
{
    public function getDst()
    {
        return $this->attributes['dst'];
    }

    public function getHost()
    {
        return $this->attributes['host'];
    }

    public function getPort()
    {
        return $this->attributes['host'];
    }

    public function getCodecIds()
    {
        return $this->attributes['codec_ids'];
    }

    public function getAuthUser()
    {
        return $this->attributes['auth_user'];
    }

    public function getAuthPassword()
    {
        return $this->attributes['auth_password'];
    }

    public function setDst($newDst)
    {
        $this->attributes['dst'] = $newDst;
    }

    public function setHost($newHost)
    {
        $this->attributes['host'] = $newHost;
    }

    public function setPort($newPort)
    {
        $this->attributes['port'] = $newPort;
    }

    public function setCodecIds($codecIds)
    {
        $this->attributes['codec_ids'] = $codecIds;
    }

    public function setAuthUser($newAuthUser)
    {
        $this->attributes['auth_user'] = $newAuthUser;
    }

    public function setAuthPassword($newAuthPassword)
    {
        $this->attributes['auth_password'] = $newAuthPassword;
    }

    protected function getType()
    {
        return 'iax2_configurations';
    }
}
