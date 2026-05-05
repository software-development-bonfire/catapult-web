<?php

namespace App\Events\KDS\FineDine;

use App\Events\KDS\KDSEventBase;

/**
 * Fine-Dine Menu Release Event
 * 
 * Broadcast when a menu item is released to the station.
 * Channel: kds-station-{deviceUid}
 */
class KDSFineDineMenuReleaseEvent extends KDSEventBase
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
        return 'kds-finedine-menu-release';
    }

    public function broadcastWith()
    {
        return [
            'item' => $this->item,
            'quantity' => $this->quantity,
            'mode' => 'finedine',
            'timestamp' => now()->toISOString(),
        ];
    }
}
