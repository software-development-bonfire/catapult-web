<?php

namespace App\Events\KDS\FastFood;

use App\Events\KDS\KDSEventBase;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Fast-Food Order Event
 * 
 * Broadcast when new Fast-Food order received
 * Sent to specific KDS device (kitchen station)
 */
class KDSFastFoodOrderEvent extends KDSEventBase
{
    protected $eventType = 'FASTFOOD_ORDER';

    public $order;
    public $items;

    public function __construct($deviceUid, $order, $items)
    {
        $this->deviceUid = $deviceUid;
        $this->order = $order;
        $this->items = $items;
    }

    protected function getChannelName(): string
    {
        return 'kds-fastfood-' . $this->deviceUid;
    }

    public function broadcastAs()
    {
        return 'kds-fastfood-order';
    }

    public function broadcastWith()
    {
        return [
            'eventType' => $this->eventType,
            'order' => $this->order,
            'items' => $this->items,
            'timestamp' => now(),
        ];
    }
}
