<?php

namespace Didww\Tests\Prices;

use Didww\Prices\Internal\ResponseSink;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ResponseSinkTest extends TestCase
{
    public function testErrorBodyReadWithinTheTimeout()
    {
        $sink = new ResponseSink(null, false, 1048576, 60.0);
        $sink->begin(new Response(404));

        $this->assertSame(5, $sink->write('error'));
        $this->assertSame('error', $sink->getErrorBody());
    }

    public function testErrorBodyNotReadAfterTheTimeout()
    {
        $sink = new ResponseSink(null, false, 1048576, 0.0);
        $sink->begin(new Response(404));
        usleep(1000);

        $this->assertSame(0, $sink->write('error'));
        $this->assertSame('', $sink->getErrorBody());
    }
}
