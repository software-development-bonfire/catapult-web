<?php

namespace App\Events\KDS\FineDine;

use App\Events\KDS\KDSEventBase;

/**
 * Fine-Dine Item Release Event
 * 
 * Broadcast when a single item is released.
 * Channel: kds-station-{deviceUid}
 */
class KDSFineDineItemReleaseEvent extends KDSEventBase
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
        return 'kds-finedine-item-release';
    }

    public function broadcastWith()
    {
        return [
            'item' => $this->item,
            'transaction' => $this->transaction,
            'quantity' => $this->quantity,
            'mode' => 'finedine',
            'timestamp' => now()->toISOString(),
        ];
    }
}
