<?php

namespace Didww\Item\Configuration;

class Pstn extends Base
{
    public function getDst()
    {
        return $this->attributes['dst'];
    }

    public function setDst($newDst)
    {
        $this->attributes['dst'] = $newDst;
    }

    public function getType(): string
    {
        return 'pstn_configurations';
    }
}
