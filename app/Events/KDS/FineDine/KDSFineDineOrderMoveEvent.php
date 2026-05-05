<?php

namespace App\Events\KDS\FineDine;

use App\Events\KDS\KDSEventBase;

/**
 * Fine-Dine Order Move Event
 * 
 * Broadcast when a whole order transaction is moved to next/previous station.
 * Channel: kds-station-{deviceUid}
 */
class KDSFineDineOrderMoveEvent extends KDSEventBase
{
    public $transaction;
    public $items;
    public $fromStation;
    public $toStation;

    public function __construct(string $deviceUid, $transaction, $items, $fromStation, $toStation)
    {
        $this->deviceUid = $deviceUid;
        $this->transaction = $transaction;
        $this->items = $items;
        $this->fromStation = $fromStation;
        $this->toStation = $toStation;
    }

    protected function getChannelName(): string
    {
        return 'kds-station-' . $this->deviceUid;
    }

    public function broadcastAs()
    {
        return 'kds-finedine-order-move';
    }

    public function broadcastWith()
    {
        return [
            'transaction' => $this->transaction,
            'items' => $this->items,
            'fromStation' => $this->fromStation,
            'toStation' => $this->toStation,
            'mode' => 'finedine',
            'timestamp' => now()->toISOString(),
        ];
    }
}
