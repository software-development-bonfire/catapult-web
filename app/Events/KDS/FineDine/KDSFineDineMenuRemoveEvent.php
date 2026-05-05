<?php

namespace App\Events\KDS\FineDine;

use App\Events\KDS\KDSEventBase;

/**
 * Fine-Dine Menu Remove Event
 * 
 * Broadcast when a menu item is removed from the station.
 * Channel: kds-station-{deviceUid}
 */
class KDSFineDineMenuRemoveEvent extends KDSEventBase
{
    public $item;

    public function __construct(string $deviceUid, $item)
    {
        $this->deviceUid = $deviceUid;
        $this->item = $item;
    }

    protected function getChannelName(): string
    {
        return 'kds-station-' . $this->deviceUid;
    }

    public function broadcastAs()
    {
        return 'kds-finedine-menu-remove';
    }

    public function broadcastWith()
    {
        return [
            'item' => $this->item,
            'mode' => 'finedine',
            'timestamp' => now()->toISOString(),
        ];
    }
}
