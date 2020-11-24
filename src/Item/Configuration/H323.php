<?php

namespace Didww\Item\Configuration;

class H323 extends Base
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
        return $this->attributes['port'];
    }

    public function getCodecIds()
    {
        return $this->attributes['codec_ids'];
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

    public function getType(): string
    {
        return 'h323_configurations';
    }
}
