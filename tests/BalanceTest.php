<?php

namespace Didww\Tests;

class BalanceTest extends BaseTest
{
    public function testFind()
    {
        $this->startVCR('balance.yml');

        $balanceDocument = \Didww\Item\Balance::find();
        $balance = $balanceDocument->getData();
        $this->assertInstanceOf('Didww\Item\Balance', $balance);
        $this->assertEquals($balance->getAttributes(), [
            'balance' => '50.00',
            'credit' => '10.00',
            'total_balance' => '60.00',
        ]);

        $this->assertEquals($balance->getTotalBalance(), 60.00);
        $this->assertEquals($balance->getCredit(), 10.00);
        $this->assertEquals($balance->getBalance(), 50.00);

        $this->stopVCR();
    }
}
