<?php

namespace Didww\Tests\Support;

/**
 * Lists the files of the test directory $this->dir, dot files included, which
 * glob('*') leaves out.
 */
trait ListsDirectoryFiles
{
    /**
     * @return list<string> the paths, sorted
     */
    private function files(): array
    {
        return array_map(fn (string $name): string => $this->dir.'/'.$name, array_values(array_diff(scandir($this->dir), ['.', '..'])));
    }
}
