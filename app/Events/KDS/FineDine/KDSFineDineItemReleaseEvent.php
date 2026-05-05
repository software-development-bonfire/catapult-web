<?php

namespace App\Events\KDS\FineDine;

use App\Enums\KDS\KDSMovementAction;
use App\Enums\KDS\KDSMovementType;
use App\Enums\KDS\KDSSystemMode;
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
        return 'kds-station-event';
    }

    public function broadcastWith()
    {
        return [
            'mode' => KDSSystemMode::FINE_DINE,
            'entity' => KDSMovementType::PER_ITEM,
            'action' => KDSMovementAction::RELEASE,
            'item' => $this->item,
            'transaction' => $this->transaction,
            'quantity' => $this->quantity,
            'timestamp' => now()->toISOString(),
        ];
    }
}
