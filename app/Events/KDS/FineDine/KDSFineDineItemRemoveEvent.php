<?php

namespace App\Events\KDS\FineDine;

use App\Events\KDS\KDSEventBase;

/**
 * Fine-Dine Item Remove Event
 * 
 * Broadcast when a single item is removed.
 * Channel: kds-station-{deviceUid}
 */
class KDSFineDineItemRemoveEvent extends KDSEventBase
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
            'mode' => 'finedine',
            'entity' => 'item',
            'action' => 'remove',
            'item' => $this->item,
            'transaction' => $this->transaction,
            'timestamp' => now()->toISOString(),
        ];
    }
}
