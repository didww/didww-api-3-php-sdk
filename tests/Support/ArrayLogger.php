<?php

namespace Didww\Tests\Support;

use Psr\Log\AbstractLogger;

/**
 * Records every log entry.
 */
final class ArrayLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string, context: array}> */
    public array $records = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->records[] = ['level' => (string) $level, 'message' => (string) $message, 'context' => $context];
    }

    /**
     * @return list<string>
     */
    public function messages(?string $level = null): array
    {
        $messages = [];
        foreach ($this->records as $record) {
            if (null === $level || $record['level'] === $level) {
                $messages[] = $record['message'];
            }
        }

        return $messages;
    }
}
