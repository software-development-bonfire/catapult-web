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
    protected $mode;

    public function __construct(string $deviceUid, $items, $order, string $mode = 'fastfood')
    {
        $this->deviceUid = $deviceUid;
        $this->items = $items;
        $this->order = $order;
        $this->mode = $mode;
    }

    protected function getChannelName(): string
    {
        return "kds-{$this->mode}-{$this->deviceUid}";
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
