<?php

namespace Didww\Prices;

use Didww\Prices\Internal\Attributes;

/**
 * A row of an SMS IN price lookup. The price is in USD.
 *
 * The id is derived from the row content: it changes when the row or its
 * price changes, so it does not track a destination over time.
 */
final readonly class SmsInPrice
{
    private function __construct(
        private string $id,
        private ?string $sourcePrefix,
        private int $sourceNumberLengthMin,
        private int $sourceNumberLengthMax,
        private ?string $destinationPrefix,
        private int $destinationNumberLengthMin,
        private int $destinationNumberLengthMax,
        private ?string $country,
        private float $price,
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
            $a->int('source_number_length_min'),
            $a->int('source_number_length_max'),
            $a->nullableString('destination_prefix'),
            $a->int('destination_number_length_min'),
            $a->int('destination_number_length_max'),
            $a->nullableString('country'),
            $a->money('price'),
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

    public function getSourceNumberLengthMin(): int
    {
        return $this->sourceNumberLengthMin;
    }

    public function getSourceNumberLengthMax(): int
    {
        return $this->sourceNumberLengthMax;
    }

    public function getDestinationPrefix(): ?string
    {
        return $this->destinationPrefix;
    }

    public function getDestinationNumberLengthMin(): int
    {
        return $this->destinationNumberLengthMin;
    }

    public function getDestinationNumberLengthMax(): int
    {
        return $this->destinationNumberLengthMax;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}
