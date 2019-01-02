<?php

namespace Didww\Item;

class DidReservation extends BaseItem
{
    use \Didww\Traits\Fetchable;
    use \Didww\Traits\Saveable;
    use \Didww\Traits\Deletable;

    protected $type = 'did_reservations';

    public function availableDid()
    {
        return $this->hasOne(AvailableDid::class);
    }

    public function setAvailableDid(AvailableDid $availableDid)
    {
        $this->availableDid()->associate($availableDid);
    }
}
