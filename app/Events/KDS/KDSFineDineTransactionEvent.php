<?php

namespace App\Events\KDS;

/**
 * Fine-Dine Transaction Event
 * 
 * Broadcast when new Fine-Dine transaction is sent to KDS.
 * Channel: kds-transaction-{deviceUid}
 */
class KDSFineDineTransactionEvent extends KDSEventBase
{
    public $transaction;
    public $items;
    public $releasing;
    public $action;

    public function __construct(string $deviceUid, $transaction, $items, $releasing = false, $action = 'NEW_ORDER')
    {
        $this->deviceUid = $deviceUid;
        $this->transaction = $transaction;
        $this->items = $items;
        $this->releasing = $releasing;
        $this->action = $action;
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
            'action' => $this->action,
            'mode' => 'finedine',
            'timestamp' => now()->toISOString(),
        ];
    }
}
