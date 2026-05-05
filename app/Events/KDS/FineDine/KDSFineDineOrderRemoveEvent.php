<?php

namespace App\Events\KDS\FineDine;

use App\Enums\KDS\KDSMovementAction;
use App\Enums\KDS\KDSMovementType;
use App\Enums\KDS\KDSSystemMode;
use App\Events\KDS\KDSEventBase;

/**
 * Fine-Dine Order Remove Event
 * 
 * Broadcast when a whole order transaction is removed.
 * Channel: kds-station-{deviceUid}
 */
class KDSFineDineOrderRemoveEvent extends KDSEventBase
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
            'mode' => KDSSystemMode::FINE_DINE,
            'entity' => KDSMovementType::PER_ORDER,
            'action' => KDSMovementAction::DELETE,
            'transaction' => $this->transaction,
            'items' => $this->items,
            'timestamp' => now()->toISOString(),
        ];
    }
}
