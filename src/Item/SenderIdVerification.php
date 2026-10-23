<?php

namespace Didww\Item;

use Didww\Enum\SenderIdVerificationStatus;
use Didww\Enum\SenderType;
use Didww\Traits\Fetchable;
use Didww\Traits\Saveable;

/**
 * Verification of a sender identity used for outbound messages.
 * Supported operations: index, show, update. Only the name and the SMS trunk
 * can be changed, and a closed verification cannot be updated. Load or
 * build() one with its id before save(): without an id save() would POST,
 * which the API does not support.
 *
 * Server-side filters: status, sender_type, sms_trunk.id, name, reference.
 *
 * Resource-level meta carries: setup_price, monthly_price.
 * Access via $verification->getMeta() (inherited from SWIS Item).
 */
class SenderIdVerification extends BaseItem
{
    use Fetchable;
    use Saveable;

    public static function getEndpoint(): string
    {
        return '/sender_id_verifications';
    }

    protected $type = 'sender_id_verifications';

    protected $visible = [
        'name',
    ];

    public function getName(): string
    {
        return $this->attributes['name'];
    }

    public function setName(string $name)
    {
        $this->attributes['name'] = $name;
    }

    public function getStatus(): SenderIdVerificationStatus|string|null
    {
        return $this->enumAttribute('status', SenderIdVerificationStatus::class);
    }

    public function getSenderType(): SenderType|string|null
    {
        return $this->enumAttribute('sender_type', SenderType::class);
    }

    /**
     * Sender IDs the verification covers; for long_code and toll_free, the
     * numbers of its DIDs.
     *
     * @return string[]
     */
    public function getSenderIds(): array
    {
        return $this->attribute('sender_ids') ?? [];
    }

    public function getReference(): ?string
    {
        return $this->attribute('reference');
    }

    /**
     * Null unless the status is changes_required, and when there are none.
     *
     * @return string[]|null
     */
    public function getRejectReasons(): ?array
    {
        return $this->attribute('reject_reasons');
    }

    /**
     * Null unless the status is changes_required.
     */
    public function getRejectComment(): ?string
    {
        return $this->attribute('reject_comment');
    }

    /**
     * Next renewal date as "YYYY-MM-DD"; null once closed.
     */
    public function getRenewDate(): ?string
    {
        return $this->attribute('renew_date');
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->dateAttribute('created_at');
    }

    public function getActivatedAt(): ?\DateTime
    {
        return $this->dateAttribute('activated_at');
    }

    public function getClosedAt(): ?\DateTime
    {
        return $this->dateAttribute('closed_at');
    }

    public function smsTrunk()
    {
        return $this->hasOne(SmsTrunk::class);
    }

    /**
     * Only `http_out`, `smpp_smsc` and `smpp_esme` trunks are accepted; null
     * unassigns the trunk.
     */
    public function setSmsTrunk(?SmsTrunk $smsTrunk)
    {
        if (null === $smsTrunk) {
            $this->smsTrunk()->dissociate();
        } else {
            $this->smsTrunk()->associate($smsTrunk);
        }
    }

    public function countries()
    {
        return $this->hasMany(Country::class);
    }

    public function dids()
    {
        return $this->hasMany(Did::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    public function identity()
    {
        return $this->hasOne(Identity::class);
    }

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    public function isActive(): bool
    {
        return SenderIdVerificationStatus::ACTIVE === $this->getStatus();
    }

    public function isChangesRequired(): bool
    {
        return SenderIdVerificationStatus::CHANGES_REQUIRED === $this->getStatus();
    }

    public function isClosed(): bool
    {
        return SenderIdVerificationStatus::CLOSED === $this->getStatus();
    }

    public function isClosingInProcess(): bool
    {
        return SenderIdVerificationStatus::CLOSING_IN_PROCESS === $this->getStatus();
    }

    public function isInProcess(): bool
    {
        return SenderIdVerificationStatus::IN_PROCESS === $this->getStatus();
    }

    /**
     * Status `new`. Item::isNew() keeps its meaning (no id yet), which save()
     * relies on.
     */
    public function isStatusNew(): bool
    {
        return SenderIdVerificationStatus::NEW === $this->getStatus();
    }
}
