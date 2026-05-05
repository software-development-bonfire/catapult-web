<?php

namespace App\Events\KDS\FineDine;

use App\Enums\KDS\KDSMovementAction;
use App\Enums\KDS\KDSMovementType;
use App\Enums\KDS\KDSSystemMode;
use App\Events\KDS\KDSEventBase;

/**
 * Fine-Dine Item Move Event
 * 
 * Broadcast when a single item is moved to next/previous station.
 * Channel: kds-station-{deviceUid}
 */
class KDSFineDineItemMoveEvent extends KDSEventBase
{
    public $item;
    public $transaction;
    public $fromStation;
    public $toStation;
    public $quantity;

    public function __construct(string $deviceUid, $item, $transaction, $fromStation, $toStation, $quantity = 1)
    {
        $this->deviceUid = $deviceUid;
        $this->item = $item;
        $this->transaction = $transaction;
        $this->fromStation = $fromStation;
        $this->toStation = $toStation;
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
            'mode' => KDSSystemMode::FINE_DINE,
            'entity' => KDSMovementType::PER_ITEM,
            'action' => KDSMovementAction::MOVE,
            'item' => $this->item,
            'transaction' => $this->transaction,
            'fromStation' => $this->fromStation,
            'toStation' => $this->toStation,
            'quantity' => $this->quantity,
            'timestamp' => now()->toISOString(),
        ];
    }
}
