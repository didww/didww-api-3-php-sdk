<?php

namespace Didww\Prices;

use Didww\Prices\Internal\Attributes;

/**
 * A row of a Voice OUT price lookup. Prices and fees are in USD; they are null
 * when getRejectCalls() is true. The next* values and getEffectiveAt()
 * announce a scheduled price change.
 *
 * The id stays the same when a re-import changes only the price.
 */
final readonly class VoiceOutPrice
{
    private function __construct(
        private string $id,
        private VoiceOutPriceCategory|string $category,
        private ?string $country,
        private ?string $network,
        private string $prefix,
        private ?float $rate,
        private ?float $connectionFee,
        private int $initialIncrement,
        private int $recurringIncrement,
        private bool $rejectCalls,
        private ?float $nextRate,
        private ?float $nextConnectionFee,
        private ?int $nextInitialIncrement,
        private ?int $nextRecurringIncrement,
        private ?\DateTimeImmutable $effectiveAt,
        private ?string $comment,
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
        $category = $a->string('category');

        return new self(
            $a->id(),
            VoiceOutPriceCategory::tryFrom($category) ?? $category,
            $a->nullableString('country'),
            $a->nullableString('network'),
            $a->string('prefix'),
            $a->nullableMoney('rate'),
            $a->nullableMoney('connection_fee'),
            $a->int('initial_increment'),
            $a->int('recurring_increment'),
            $a->bool('reject_calls'),
            $a->nullableMoney('next_rate'),
            $a->nullableMoney('next_connection_fee'),
            $a->nullableInt('next_initial_increment'),
            $a->nullableInt('next_recurring_increment'),
            $a->nullableDateTime('effective_at'),
            $a->nullableString('comment'),
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * The category, or the raw value when this SDK does not know it.
     */
    public function getCategory(): VoiceOutPriceCategory|string
    {
        return $this->category;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getNetwork(): ?string
    {
        return $this->network;
    }

    /**
     * The destination prefix of the plan that priced the number.
     */
    public function getPrefix(): string
    {
        return $this->prefix;
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

    public function getInitialIncrement(): int
    {
        return $this->initialIncrement;
    }

    public function getRecurringIncrement(): int
    {
        return $this->recurringIncrement;
    }

    public function getRejectCalls(): bool
    {
        return $this->rejectCalls;
    }

    public function getNextRate(): ?float
    {
        return $this->nextRate;
    }

    public function getNextConnectionFee(): ?float
    {
        return $this->nextConnectionFee;
    }

    public function getNextInitialIncrement(): ?int
    {
        return $this->nextInitialIncrement;
    }

    public function getNextRecurringIncrement(): ?int
    {
        return $this->nextRecurringIncrement;
    }

    /**
     * When the next* values take effect.
     */
    public function getEffectiveAt(): ?\DateTimeImmutable
    {
        return $this->effectiveAt;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }
}
