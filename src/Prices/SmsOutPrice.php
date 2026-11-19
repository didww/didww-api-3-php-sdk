<?php

namespace Didww\Prices;

use Didww\Prices\Internal\Attributes;

/**
 * A row of an SMS OUT (A2P, P2P or OTP) price lookup. The price is in USD; it
 * is null when getRejectSms() is true.
 *
 * The id is derived from the row content: it changes when the row or its
 * price changes, so it does not track a destination over time.
 */
final readonly class SmsOutPrice
{
    private function __construct(
        private string $id,
        private ?string $sourcePrefix,
        private ?string $destinationPrefix,
        private ?string $country,
        private ?int $mcc,
        private ?string $mccName,
        private ?int $mnc,
        private ?string $mncName,
        private bool $rejectSms,
        private ?float $price,
    ) {
    }

    /**
     * Builds the row from a JSON:API resource object of a lookup answer.
     *
     * @param array<mixed> $resource
     *
     * @throws \UnexpectedValueException when an attribute is missing or of the wrong type
     */
    public static function fromJsonApi(array $resource): self
    {
        $a = Attributes::of($resource);

        return new self(
            $a->id(),
            $a->nullableString('source_prefix'),
            $a->nullableString('destination_prefix'),
            $a->nullableString('country'),
            $a->nullableInt('mcc'),
            $a->nullableString('mcc_name'),
            $a->nullableInt('mnc'),
            $a->nullableString('mnc_name'),
            $a->bool('reject_sms'),
            $a->nullableMoney('price'),
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSourcePrefix(): ?string
    {
        return $this->sourcePrefix;
    }

    public function getDestinationPrefix(): ?string
    {
        return $this->destinationPrefix;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getMcc(): ?int
    {
        return $this->mcc;
    }

    public function getMccName(): ?string
    {
        return $this->mccName;
    }

    public function getMnc(): ?int
    {
        return $this->mnc;
    }

    public function getMncName(): ?string
    {
        return $this->mncName;
    }

    public function getRejectSms(): bool
    {
        return $this->rejectSms;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }
}
