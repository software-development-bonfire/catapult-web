<?php

namespace App\Events\KDS\FastFood;

use App\Events\KDS\KDSEventBase;

/**
 * Fast-Food Item Remove Event
 * 
 * Broadcast when a single item is removed.
 * Channel: kds-station-{deviceUid}
 */
class KDSFastFoodItemRemoveEvent extends KDSEventBase
{
    public $item;
    public $transaction;

    public function __construct(string $deviceUid, $item, $transaction)
    {
        $this->deviceUid = $deviceUid;
        $this->item = $item;
        $this->transaction = $transaction;
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
            'entity' => 'item',
            'action' => 'remove',
            'item' => $this->item,
            'transaction' => $this->transaction,
            'timestamp' => now()->toISOString(),
        ];
    }
}
