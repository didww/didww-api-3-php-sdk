<?php

namespace Didww\Tests\Support;

/**
 * The arguments kept by the frames of the SDK's own classes (or of the classes
 * under $prefix) in the traces of an exception chain, as print_r() shows them.
 */
final class TraceArgs
{
    public static function ofSdkFrames(\Throwable $e, string $prefix = 'Didww\\'): string
    {
        $dump = '';
        for ($current = $e; null !== $current; $current = $current->getPrevious()) {
            foreach ($current->getTrace() as $frame) {
                $class = $frame['class'] ?? '';
                if (str_starts_with($class, $prefix) && !str_starts_with($class, 'Didww\\Tests\\')) {
                    $dump .= $class.'::'.$frame['function'].print_r($frame['args'] ?? [], true);
                }
            }
        }

        return $dump;
    }

    /**
     * Runs $call with the arguments kept in exception traces, whatever
     * zend.exception_ignore_args says in php.ini.
     */
    public static function keepingArgs(callable $call): mixed
    {
        $previous = ini_set('zend.exception_ignore_args', '0');
        try {
            return $call();
        } finally {
            if (false !== $previous) {
                ini_set('zend.exception_ignore_args', $previous);
            }
        }
    }
}
