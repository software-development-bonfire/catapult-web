<?php

namespace App\Events\KDS\Common;

use App\Events\KDS\KDSEventBase;

/**
 * Order Done Event
 * 
 * Broadcast when entire order marked as complete.
 * Sent per-device to each KDS station that had the order.
 */
class KDSOrderDoneEvent extends KDSEventBase
{
    protected $eventType = 'ORDER_DONE';

    public $items;
    public $order;

    public function __construct(string $deviceUid, $items, $order)
    {
        $this->deviceUid = $deviceUid;
        $this->items = $items;
        $this->order = $order;
    }

    public function broadcastAs()
    {
        return 'kds-order-done';
    }

    public function broadcastWith()
    {
        return [
            'eventType' => $this->eventType,
            'items' => $this->items,
            'order' => $this->order,
            'timestamp' => now(),
        ];
    }
}
