<?php

namespace App\Events\KDS\FastFood;

use App\Events\KDS\KDSEventBase;

/**
 * Fast-Food Item Release Event
 * 
 * Broadcast when a single item is released.
 * Channel: kds-station-{deviceUid}
 */
class KDSFastFoodItemReleaseEvent extends KDSEventBase
{
    public $item;
    public $transaction;
    public $quantity;

    public function __construct(string $deviceUid, $item, $transaction, $quantity = 1)
    {
        $this->deviceUid = $deviceUid;
        $this->item = $item;
        $this->transaction = $transaction;
        $this->quantity = $quantity;
    }

    protected function getChannelName(): string
    {
        return 'kds-station-' . $this->deviceUid;
    }

    public function broadcastAs()
    {
        return 'kds-fastfood-item-release';
    }

    public function broadcastWith()
    {
        return [
            'item' => $this->item,
            'transaction' => $this->transaction,
            'quantity' => $this->quantity,
            'mode' => 'fastfood',
            'timestamp' => now()->toISOString(),
        ];
    }
}
