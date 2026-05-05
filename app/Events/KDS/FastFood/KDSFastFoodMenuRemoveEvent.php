<?php

namespace App\Events\KDS\FastFood;

use App\Enums\KDS\KDSMovementAction;
use App\Enums\KDS\KDSMovementType;
use App\Enums\KDS\KDSSystemMode;
use App\Events\KDS\KDSEventBase;

/**
 * Fast-Food Menu Remove Event
 * 
 * Broadcast when a menu item is removed from the station.
 * Channel: kds-station-{deviceUid}
 */
class KDSFastFoodMenuRemoveEvent extends KDSEventBase
{
    public $item;

    public function __construct(string $deviceUid, $item)
    {
        $this->deviceUid = $deviceUid;
        $this->item = $item;
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
            'action' => KDSMovementAction::DELETE,
            'item' => $this->item,
            'timestamp' => now()->toISOString(),
        ];
    }
}
