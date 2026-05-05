<?php

namespace App\Events\KDS\FastFood;

use App\Events\KDS\KDSEventBase;

/**
 * Fast-Food Order Done Event
 * 
 * Broadcast when a whole order transaction is marked as done.
 * Channel: kds-station-{deviceUid}
 */
class KDSFastFoodOrderDoneEvent extends KDSEventBase
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
            'action' => 'done',
            'transaction' => $this->transaction,
            'items' => $this->items,
            'timestamp' => now()->toISOString(),
        ];
    }
}
