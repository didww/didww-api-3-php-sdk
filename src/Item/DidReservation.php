<?php

namespace Didww\Item;

use Didww\Traits\Deletable;
use Didww\Traits\Fetchable;
use Didww\Traits\Saveable;

class DidReservation extends BaseItem
{
    use Fetchable;
    use Saveable;
    use Deletable;

    protected $type = 'did_reservations';

    public function availableDid()
    {
        return $this->hasOne(AvailableDid::class);
    }

    public function setAvailableDid(AvailableDid $availableDid)
    {
        $this->availableDid()->associate($availableDid);
    }

    public function getExpireAt(): ?\DateTime
    {
        return $this->dateAttribute('expire_at');
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->dateAttribute('created_at');
    }

    public function getDescription(): string
    {
        return $this->getAttributes()['description'];
    }

    public function setDescription(string $description)
    {
        $this->attributes['description'] = $description;
    }
}
