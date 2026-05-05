<?php

namespace App\Events\KDS\FineDine;

use App\Events\KDS\KDSEventBase;

/**
 * Fine-Dine Menu Move Event
 * 
 * Broadcast when a menu item is moved to next/previous station.
 * Channel: kds-station-{deviceUid}
 */
class KDSFineDineMenuMoveEvent extends KDSEventBase
{
    public $item;
    public $fromStation;
    public $toStation;
    public $quantity;

    public function __construct(string $deviceUid, $item, $fromStation, $toStation, $quantity = 1)
    {
        $this->deviceUid = $deviceUid;
        $this->item = $item;
        $this->fromStation = $fromStation;
        $this->toStation = $toStation;
        $this->quantity = $quantity;
    }

    protected function getChannelName(): string
    {
        return 'kds-station-' . $this->deviceUid;
    }

    public function broadcastAs()
    {
        return 'kds-finedine-menu-move';
    }

    public function broadcastWith()
    {
        return [
            'item' => $this->item,
            'fromStation' => $this->fromStation,
            'toStation' => $this->toStation,
            'quantity' => $this->quantity,
            'mode' => 'finedine',
            'timestamp' => now()->toISOString(),
        ];
    }
}
