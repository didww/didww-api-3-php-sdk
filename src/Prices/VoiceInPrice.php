<?php

namespace Didww\Prices;

use Didww\Prices\Internal\Attributes;

/**
 * A row of a Voice IN price lookup. Prices and fees are in USD, VAT excluded;
 * they are null when getRejectCalls() is true: the destination is refused,
 * which is still an answer.
 *
 * The id names the answering row of the plan.
 */
final readonly class VoiceInPrice
{
    private function __construct(
        private string $id,
        private string $destination,
        private ?string $country,
        private ?string $srcCountryName,
        private string $sourceType,
        private ?float $rate,
        private ?float $connectionFee,
        private int $initialInterval,
        private int $nextInterval,
        private bool $rejectCalls,
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
            $a->string('destination'),
            $a->nullableString('country'),
            $a->nullableString('src_country_name'),
            $a->string('source_type'),
            $a->nullableMoney('rate'),
            $a->nullableMoney('connection_fee'),
            $a->int('initial_interval'),
            $a->int('next_interval'),
            $a->bool('reject_calls'),
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * The prefix of the plan that priced the number.
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * The source country of the call the row is priced for; null for any.
     */
    public function getSrcCountryName(): ?string
    {
        return $this->srcCountryName;
    }

    /**
     * The calling party category, such as "cellular" or "fixed".
     */
    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    /**
     * The per-minute rate.
     */
    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function getConnectionFee(): ?float
    {
        return $this->connectionFee;
    }

    public function getInitialInterval(): int
    {
        return $this->initialInterval;
    }

    public function getNextInterval(): int
    {
        return $this->nextInterval;
    }

    public function getRejectCalls(): bool
    {
        return $this->rejectCalls;
    }
}
