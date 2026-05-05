<?php

namespace App\Events\KDS\FineDine;

use App\Events\KDS\KDSEventBase;

/**
 * Fine-Dine Item Done Event
 * 
 * Broadcast when a single item is marked as done.
 * Channel: kds-station-{deviceUid}
 */
class KDSFineDineItemDoneEvent extends KDSEventBase
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
        return 'kds-station-event';
    }

    public function broadcastWith()
    {
        return [
            'mode' => 'finedine',
            'entity' => 'item',
            'action' => 'done',
            'item' => $this->item,
            'transaction' => $this->transaction,
            'quantity' => $this->quantity,
            'timestamp' => now()->toISOString(),
        ];
    }
}
