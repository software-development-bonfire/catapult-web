<?php

namespace App\Events\KDS\FineDine;

use App\Events\KDS\KDSEventBase;

/**
 * Fine-Dine Order Remove Event
 * 
 * Broadcast when a whole order transaction is removed.
 * Channel: kds-station-{deviceUid}
 */
class KDSFineDineOrderRemoveEvent extends KDSEventBase
{
    public $transaction;
    public $items;

    public function __construct(string $deviceUid, $transaction, $items = [])
    {
        $this->deviceUid = $deviceUid;
        $this->transaction = $transaction;
        $this->items = $items;
    }

    protected function getChannelName(): string
    {
        return 'kds-station-' . $this->deviceUid;
    }

    public function broadcastAs()
    {
        return 'kds-finedine-order-remove';
    }

    public function broadcastWith()
    {
        return [
            'transaction' => $this->transaction,
            'items' => $this->items,
            'mode' => 'finedine',
            'timestamp' => now()->toISOString(),
        ];
    }
}
