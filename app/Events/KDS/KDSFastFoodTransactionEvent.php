<?php

namespace App\Events\KDS;

/**
 * Fast-Food Transaction Event
 * 
 * Broadcast when new Fast-Food transaction is sent to KDS.
 * Channel: kds-transaction-{deviceUid}
 */
class KDSFastFoodTransactionEvent extends KDSEventBase
{
    public $transaction;
    public $items;
    public $releasing;

    public function __construct(string $deviceUid, $transaction, $items, $releasing = false)
    {
        $this->deviceUid = $deviceUid;
        $this->transaction = $transaction;
        $this->items = $items;
        $this->releasing = $releasing;
    }

    protected function getChannelName(): string
    {
        return 'kds-transaction-' . $this->deviceUid;
    }

    public function broadcastAs()
    {
        return 'kds-transaction-event';
    }

    public function broadcastWith()
    {
        return [
            'transaction' => $this->transaction,
            'items' => $this->items,
            'releasing' => $this->releasing,
            'mode' => 'fastfood',
            'timestamp' => now()->toISOString(),
        ];
    }
}
