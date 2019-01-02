<?php

namespace Didww\Tests;

class OrderTest extends BaseTest
{
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
}
