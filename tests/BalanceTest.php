<?php

namespace Didww\Tests;

class BalanceTest extends BaseTest
{
    public function testFind()
    {
        $this->startVCR('balance.yml');

        $balance = \Didww\Item\Balance::find();
        $this->assertInstanceOf('Didww\Item\Balance', $balance->getData());
        $this->assertEquals($balance->getData()->getAttributes(), [
            'balance' => '50.00',
            'credit' => '10.00',
            'total_balance' => '60.00',
        ]);

        $this->stopVCR();
    }
}
