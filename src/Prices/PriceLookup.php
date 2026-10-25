<?php

namespace Didww\Prices;

/**
 * The answer of a price lookup. It always holds at least one row: no match is
 * PricesErrorKind::PRICE_NOT_FOUND.
 *
 * @template T of VoiceInPrice|VoiceOutPrice|SmsOutPrice|SmsInPrice
 *
 * @implements \IteratorAggregate<int, T>
 */
final readonly class PriceLookup implements \IteratorAggregate, \Countable
{
    /**
     * @param list<T> $rows
     */
    public function __construct(private array $rows, private int $totalRecords)
    {
    }

    /**
     * @return list<T>
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * The total_records of the answer.
     */
    public function getTotalRecords(): int
    {
        return $this->totalRecords;
    }

    /**
     * @return \ArrayIterator<int, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->rows);
    }

    public function count(): int
    {
        return count($this->rows);
    }
}
