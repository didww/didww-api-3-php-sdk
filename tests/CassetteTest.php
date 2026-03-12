<?php

namespace Didww\Tests;

abstract class CassetteTest extends BaseTest
{
    abstract protected function getCassetteName(): string;

    protected function setUp(): void
    {
        parent::setUp();
        $this->startVCR($this->getCassetteName());
    }

    protected function tearDown(): void
    {
        $this->stopVCR();
    }
}
