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

    public function getExpireAt(): \DateTime
    {
        return new \DateTime($this->getAttributes()['expire_at']);
    }

    public function getCreatedAt(): \DateTime
    {
        return new \DateTime($this->getAttributes()['created_at']);
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
