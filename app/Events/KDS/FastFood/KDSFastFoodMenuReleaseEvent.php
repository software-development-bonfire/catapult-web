<?php

namespace App\Events\KDS\FastFood;

use App\Events\KDS\KDSEventBase;

/**
 * Fast-Food Menu Release Event
 * 
 * Broadcast when a menu item is released to the station.
 * Channel: kds-station-{deviceUid}
 */
class KDSFastFoodMenuReleaseEvent extends KDSEventBase
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
        return 'kds-fastfood-menu-release';
    }

    public function broadcastWith()
    {
        return [
            'item' => $this->item,
            'quantity' => $this->quantity,
            'mode' => 'fastfood',
            'timestamp' => now()->toISOString(),
        ];
    }
}
