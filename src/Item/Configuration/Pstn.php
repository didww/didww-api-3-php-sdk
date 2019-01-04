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

    protected function getType()
    {
        return 'pstn_configurations';
    }
}
