<?php

namespace App\Events\KDS\FineDine;

use App\Events\KDS\KDSEventBase;

/**
 * Fine-Dine Order Release Event
 * 
 * Broadcast when a whole order transaction is released.
 * Channel: kds-station-{deviceUid}
 */
class KDSFineDineOrderReleaseEvent extends KDSEventBase
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
        return 'kds-finedine-order-release';
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
