<?php

namespace App\Events\KDS\FineDine;

use App\Events\KDS\KDSEventBase;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Fine-Dine Order Event
 * 
 * Broadcast when new Fine-Dine order or batch received
 * Sent to specific KDS device (kitchen station)
 */
class KDSFineDineOrderEvent extends KDSEventBase
{
    protected $eventType = 'FINEDINE_ORDER';

    public $order;
    public $items;
    public $action; // NEW_ORDER, BATCH_ADDED

    public function __construct($deviceUid, $order, $items, $action = 'NEW_ORDER')
    {
        $this->deviceUid = $deviceUid;
        $this->order = $order;
        $this->items = $items;
        $this->action = $action;
    }

    public function broadcastAs()
    {
        return 'kds-finedine-order';
    }

    public function broadcastWith()
    {
        return [
            'eventType' => $this->eventType,
            'order' => $this->order,
            'items' => $this->items,
            'action' => $this->action,
            'timestamp' => now(),
        ];
    }
}
