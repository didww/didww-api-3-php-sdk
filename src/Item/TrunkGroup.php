<?php

namespace Didww\Item;

class TrunkGroup extends BaseItem
{
    use \Didww\Traits\Fetchable;
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Deletable;

    protected $type = 'trunk_groups';

    public function trunks()
    {
        return $this->hasMany(Trunk::class);
    }


    public function setTrunks(\Swis\JsonApi\Client\Collection $trunks)
    {
        $this->trunks()->associate($trunks);
    }
}
