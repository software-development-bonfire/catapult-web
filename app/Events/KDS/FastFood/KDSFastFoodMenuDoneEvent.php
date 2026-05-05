<?php

namespace App\Events\KDS\FastFood;

use App\Events\KDS\KDSEventBase;

/**
 * Fast-Food Menu Done Event
 * 
 * Broadcast when a menu item is marked as done.
 * Channel: kds-station-{deviceUid}
 */
class KDSFastFoodMenuDoneEvent extends KDSEventBase
{
    public $item;
    public $quantity;

    public function __construct(string $deviceUid, $item, $quantity = 1)
    {
        $this->deviceUid = $deviceUid;
        $this->item = $item;
        $this->quantity = $quantity;
    }

    protected function getChannelName(): string
    {
        return 'kds-station-' . $this->deviceUid;
    }

    public function broadcastAs()
    {
        return 'kds-station-event';
    }

    public function broadcastWith()
    {
        return [
            'mode' => 'fastfood',
            'entity' => 'menu',
            'action' => 'done',
            'item' => $this->item,
            'quantity' => $this->quantity,
            'timestamp' => now()->toISOString(),
        ];
    }
}
