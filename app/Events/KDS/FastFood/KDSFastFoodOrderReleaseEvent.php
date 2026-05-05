<?php

namespace App\Events\KDS\FastFood;

use App\Events\KDS\KDSEventBase;

/**
 * Fast-Food Order Release Event
 * 
 * Broadcast when a whole order transaction is released.
 * Channel: kds-station-{deviceUid}
 */
class KDSFastFoodOrderReleaseEvent extends KDSEventBase
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
        return 'kds-station-event';
    }

    public function broadcastWith()
    {
        return [
            'mode' => 'fastfood',
            'entity' => 'order',
            'action' => 'release',
            'transaction' => $this->transaction,
            'items' => $this->items,
            'timestamp' => now()->toISOString(),
        ];
    }
}
