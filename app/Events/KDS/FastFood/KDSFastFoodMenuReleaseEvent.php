<?php

namespace App\Events\KDS\FastFood;

use App\Enums\KDS\KDSMovementAction;
use App\Enums\KDS\KDSMovementType;
use App\Enums\KDS\KDSSystemMode;
use App\Events\KDS\KDSEventBase;

/**
 * Fast-Food Menu Release Event
 * 
 * Broadcast when a menu item is released to the station.
 * Channel: kds-station-{deviceUid}
 */
class KDSFastFoodMenuReleaseEvent extends KDSEventBase
{
    public $item;
    public $quantity;

    public function __construct(string $deviceUid, $item, $quantity = 1)
    {
        $this->deviceUid = $deviceUid;
        $this->item = $item;
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
            'mode' => KDSSystemMode::FAST_FOOD,
            'entity' => KDSMovementType::PER_MENU,
            'action' => KDSMovementAction::RELEASE,
            'item' => $this->item,
            'quantity' => $this->quantity,
            'timestamp' => now()->toISOString(),
        ];
    }
}
