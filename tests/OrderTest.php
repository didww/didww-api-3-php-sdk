<?php

namespace Didww\Tests;

use Didww\Enum\CallbackMethod;
use Didww\Enum\OrderStatus;

class OrderTest extends BaseTest
{
    public function testFind()
    {
        $this->startVCR('orders.yml');

        $uuid = '9df11dac-9d83-448c-8866-19c998be33db';
        $orderDocument = \Didww\Item\Order::find($uuid);
        $order = $orderDocument->getData();
        $this->assertInstanceOf('Didww\Item\Order', $order);
        $this->assertContainsOnlyInstancesOf('Didww\Item\OrderItem\Generic', $order->getAttributes()['items']);
        $this->assertEquals($order->getAmount(), 25.07);

        $this->assertEquals($order->getStatus(), OrderStatus::COMPLETED);
        $this->assertEquals($order->getCreatedAt(), new \DateTime('2018-08-17T09:48:48.440Z'));
        $this->assertEquals($order->getDescription(), 'Payment processing fee');
        $this->assertEquals($order->getReference(), 'SPT-474057');
        $item = $order->getItems()[0];
        $this->assertEquals($item->getMrc(), 0.0);
        $this->assertEquals($item->getNrc(), 25.07);
        $this->assertEquals($item->getBilledTo(), '2018-09-16');
        $this->assertEquals($item->getBilledFrom(), '2018-08-17');
        $this->assertEquals($item->getProratedMrc(), false);
        $this->assertEquals($item->getQty(), 1);
        $this->stopVCR();
    }

    public function testOrderBillingCyclesCountSave()
    {
        $this->startVCR('orders.yml');

        $attributes = [
            'allow_back_ordering' => true,
            'items' => [
                new \Didww\Item\OrderItem\Did([
                    'sku_id' => 'f36d2812-2195-4385-85e8-e59c3484a8bc',
                    'qty' => 1,
                    'billing_cycles_count' => 5,
                ]),
            ],
        ];
        $order = new \Didww\Item\Order($attributes);
        $this->assertEquals($order->toJsonApiArray(), [
            'type' => 'orders',
            'attributes' => [
                'allow_back_ordering' => true,
                'items' => [
                    [
                        'type' => 'did_order_items',
                        'attributes' => [
                            'qty' => 1,
                            'sku_id' => 'f36d2812-2195-4385-85e8-e59c3484a8bc',
                            'billing_cycles_count' => 5,
                        ],
                    ],
                ],
            ],
        ]);
        $orderDocument = $order->save();
        $order = $orderDocument->getData();
        $this->assertInstanceOf('Didww\Item\Order', $order);
        $this->assertContainsOnlyInstancesOf('Didww\Item\OrderItem\Did', $order->getAttributes()['items']);

        $this->stopVCR();
    }

    public function testOrderSkuSave()
    {
        $this->startVCR('orders.yml');

        $attributes = [
            'allow_back_ordering' => true,
            'items' => [
                new \Didww\Item\OrderItem\Did(['sku_id' => 'acc46374-0b34-4912-9f67-8340339db1e5', 'qty' => 2]),
                new \Didww\Item\OrderItem\Did(['sku_id' => 'f36d2812-2195-4385-85e8-e59c3484a8bc', 'qty' => 1]),
            ],
        ];
        $order = new \Didww\Item\Order($attributes);
        $this->assertEquals($order->toJsonApiArray(), [
            'type' => 'orders',
            'attributes' => [
                'allow_back_ordering' => true,
                'items' => [
                    [
                        'type' => 'did_order_items',
                        'attributes' => [
                            'qty' => 2,
                            'sku_id' => 'acc46374-0b34-4912-9f67-8340339db1e5',
                        ],
                    ],
                    [
                        'type' => 'did_order_items',
                        'attributes' => [
                            'qty' => 1,
                            'sku_id' => 'f36d2812-2195-4385-85e8-e59c3484a8bc',
                        ],
                    ],
                ],
            ],
        ]);
        $orderDocument = $order->save();
        $order = $orderDocument->getData();
        $this->assertInstanceOf('Didww\Item\Order', $order);
        $this->assertContainsOnlyInstancesOf('Didww\Item\OrderItem\Did', $order->getAttributes()['items']);

        $this->stopVCR();
    }

    public function testOrderAvailableDidSave()
    {
        $this->startVCR('orders.yml');

        $attributes = [
            'items' => [
                new \Didww\Item\OrderItem\AvailableDid([
                    'sku_id' => 'acc46374-0b34-4912-9f67-8340339db1e5',
                    'available_did_id' => 'c43441e3-82d4-4d84-93e2-80998576c1ce',
                ]),
            ],
        ];
        $order = new \Didww\Item\Order($attributes);
        $this->assertEquals($order->toJsonApiArray(), [
            'type' => 'orders',
            'attributes' => [
                'items' => [
                    [
                        'type' => 'did_order_items',
                        'attributes' => [
                            'sku_id' => 'acc46374-0b34-4912-9f67-8340339db1e5',
                            'available_did_id' => 'c43441e3-82d4-4d84-93e2-80998576c1ce',
                        ],
                    ],
                ],
            ],
        ]);
        $orderDocument = $order->save();
        $order = $orderDocument->getData();
        $this->assertInstanceOf('Didww\Item\Order', $order);
        $this->assertInstanceOf('Didww\Item\OrderItem\Did', $order->getAttributes()['items'][0]);
        $this->stopVCR();
    }

    public function testOrderReservationSave()
    {
        $this->startVCR('orders.yml');

        $attributes = [
            'items' => [
                new \Didww\Item\OrderItem\ReservationDid([
                    'sku_id' => '32840f64-5c3f-4278-8c8d-887fbe2f03f4',
                    'did_reservation_id' => 'e3ed9f97-1058-430c-9134-38f1c614ee9f',
                ]),
            ],
        ];
        $order = new \Didww\Item\Order($attributes);
        $this->assertEquals($order->toJsonApiArray(), [
            'type' => 'orders',
            'attributes' => [
                'items' => [
                    [
                        'type' => 'did_order_items',
                        'attributes' => [
                            'sku_id' => '32840f64-5c3f-4278-8c8d-887fbe2f03f4',
                            'did_reservation_id' => 'e3ed9f97-1058-430c-9134-38f1c614ee9f',
                        ],
                    ],
                ],
            ],
        ]);
        $orderDocument = $order->save();
        $order = $orderDocument->getData();
        $this->assertInstanceOf('Didww\Item\Order', $order);
        $this->assertInstanceOf('Didww\Item\OrderItem\Did', $order->getAttributes()['items'][0]);
        $this->stopVCR();
    }

    public function testOrderCapacitySave()
    {
        $this->startVCR('orders.yml');

        $attributes = [
            'items' => [
                new \Didww\Item\OrderItem\Capacity([
                    'capacity_pool_id' => 'b7522a31-4bf3-4c23-81e8-e7a14b23663f',
                    'qty' => 1,
                ]),
            ],
        ];

        $order = new \Didww\Item\Order($attributes);

        $this->assertEquals($order->toJsonApiArray(), [
            'type' => 'orders',
            'attributes' => [
                'items' => [
                    [
                        'type' => 'capacity_order_items',
                        'attributes' => [
                            'capacity_pool_id' => 'b7522a31-4bf3-4c23-81e8-e7a14b23663f',
                            'qty' => 1,
                        ],
                    ],
                ],
            ],
        ]);
        $orderDocument = $order->save();
        $order = $orderDocument->getData();
        $this->assertInstanceOf('Didww\Item\Order', $order);
        $this->assertInstanceOf('Didww\Item\OrderItem\Capacity', $order->getAttributes()['items'][0]);
        $this->stopVCR();
    }

    public function testCreateOrderWithNanpaPrefix()
    {
        $this->startVCR('orders.yml');

        $attributes = [
            'allow_back_ordering' => true,
            'items' => [
                new \Didww\Item\OrderItem\Did([
                    'sku_id' => 'fe77889c-f05a-40ad-a845-96aca3c28054',
                    'nanpa_prefix_id' => 'eeed293b-f3d8-4ef8-91ef-1b077d174b3b',
                    'qty' => 1,
                ]),
            ],
        ];
        $order = new \Didww\Item\Order($attributes);
        $this->assertEquals($order->toJsonApiArray(), [
            'type' => 'orders',
            'attributes' => [
                'allow_back_ordering' => true,
                'items' => [
                    [
                        'type' => 'did_order_items',
                        'attributes' => [
                            'qty' => 1,
                            'sku_id' => 'fe77889c-f05a-40ad-a845-96aca3c28054',
                            'nanpa_prefix_id' => 'eeed293b-f3d8-4ef8-91ef-1b077d174b3b',
                        ],
                    ],
                ],
            ],
        ]);

        $orderDocument = $order->save();
        $order = $orderDocument->getData();
        $this->assertInstanceOf('Didww\Item\Order', $order);
        $this->assertContainsOnlyInstancesOf('Didww\Item\OrderItem\Did', $order->getAttributes()['items']);
        $this->stopVCR();
    }

    public function testOrderSkuSaveWithCallback()
    {
        $this->startVCR('orders_with_callback.yml');

        $attributes = [
            'allow_back_ordering' => true,
            'items' => [
                new \Didww\Item\OrderItem\Did([
                    'sku_id' => 'f36d2812-2195-4385-85e8-e59c3484a8bc',
                    'qty' => 1,
                ]),
            ],
        ];
        $order = new \Didww\Item\Order($attributes);
        $order->setCallbackUrl('https://example.com/callback');
        $order->setCallbackMethod('POST');
        $this->assertEquals($order->toJsonApiArray(), [
            'type' => 'orders',
            'attributes' => [
                'allow_back_ordering' => true,
                'callback_url' => 'https://example.com/callback',
                'callback_method' => 'POST',
                'items' => [
                    [
                        'type' => 'did_order_items',
                        'attributes' => [
                            'qty' => 1,
                            'sku_id' => 'f36d2812-2195-4385-85e8-e59c3484a8bc',
                        ],
                    ],
                ],
            ],
        ]);
        $orderDocument = $order->save();
        $order = $orderDocument->getData();
        $this->assertInstanceOf('Didww\Item\Order', $order);
        $this->assertContainsOnlyInstancesOf('Didww\Item\OrderItem\Did', $order->getAttributes()['items']);
        $this->assertEquals($order->getCallbackUrl(), 'https://example.com/callback');
        $this->assertEquals($order->getCallbackMethod(), CallbackMethod::POST);

        $this->stopVCR();
    }

    public function testAvailableDidOrderItemSetters()
    {
        $item = new \Didww\Item\OrderItem\AvailableDid();

        $item->setSkuId('sku-123');
        $this->assertEquals('sku-123', $item->getSkuId());

        $item->setAvailableDidId('did-456');
        $this->assertEquals('did-456', $item->getAttributes()['available_did_id']);

        $availableDid = new \Didww\Item\AvailableDid();
        $availableDid->setId('did-789');
        $item->setAvailableDid($availableDid);
        $this->assertEquals('did-789', $item->getAttributes()['available_did_id']);

        $this->assertEquals('did_order_items', $item->getType());
    }

    public function testReservationDidOrderItemSetters()
    {
        $item = new \Didww\Item\OrderItem\ReservationDid();

        $item->setSkuId('sku-abc');
        $this->assertEquals('sku-abc', $item->getSkuId());

        $item->setDidReservationId('res-123');
        $this->assertEquals('res-123', $item->getAttributes()['did_reservation_id']);

        $reservation = new \Didww\Item\DidReservation();
        $reservation->setId('res-456');
        $item->setDidReservation($reservation);
        $this->assertEquals('res-456', $item->getAttributes()['did_reservation_id']);

        $this->assertEquals('did_order_items', $item->getType());
    }

    public function testCapacityOrderItemSetters()
    {
        $item = new \Didww\Item\OrderItem\Capacity();

        $item->setQty(5);
        $this->assertEquals(5, $item->getQty());

        $item->setCapacityPoolId('pool-123');
        $this->assertEquals('pool-123', $item->getCapacityPoolId());

        $pool = new \Didww\Item\CapacityPool();
        $pool->setId('pool-456');
        $item->setCapacityPool($pool);
        $this->assertEquals('pool-456', $item->getCapacityPoolId());

        $this->assertEquals('capacity_order_items', $item->getType());
    }
}
