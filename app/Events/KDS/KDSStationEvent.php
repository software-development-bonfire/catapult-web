<?php

namespace App\Events\KDS;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Unified KDS Station Event
 *
 * All station-level events (movements, releases, batches) are broadcast
 * on the device's private channel. If received, it's for this device.
 *
 * EventTypes:
 * - FASTFOOD_RELEASE, FASTFOOD_MOVEMENT
 * - FINEDINE_RELEASE, FINEDINE_MOVEMENT, FINEDINE_PARTIAL
 */
class KDSStationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deviceUid;
    public $eventType;
    public $payload;

    public function __construct($deviceUid, $eventType, $payload)
    {
        $this->deviceUid = $deviceUid;
        $this->eventType = $eventType;
        $this->payload = $payload;
    }

    public function broadcastOn()
    {
        $prefix = str_starts_with($this->eventType, 'FINEDINE') ? 'finedine' : 'fastfood';
        return new PrivateChannel("kds-{$prefix}-{$this->deviceUid}");
    }

    public function broadcastAs()
    {
        return 'kds-station-event';
    }

    public function broadcastWith()
    {
        return array_merge($this->payload, [
            'eventType' => $this->eventType,
            'timestamp' => now()->toISOString(),
        ]);
    }

    // ── Factory methods ──

    public static function fastFoodRelease(string $deviceUid, $item): self
    {
        return new self($deviceUid, 'FASTFOOD_RELEASE', ['item' => $item]);
    }

    public static function fastFoodMovement(string $deviceUid, $item, $toStation, $quantity): self
    {
        return new self($deviceUid, 'FASTFOOD_MOVEMENT', [
            'item' => $item,
            'toStation' => $toStation,
            'quantity' => $quantity,
        ]);
    }

    public static function fineDineRelease(string $deviceUid, $item, $order): self
    {
        return new self($deviceUid, 'FINEDINE_RELEASE', [
            'item' => $item,
            'order' => $order,
        ]);
    }

    public static function fineDineMovement(string $deviceUid, $item, $order, $toStation): self
    {
        return new self($deviceUid, 'FINEDINE_MOVEMENT', [
            'item' => $item,
            'order' => $order,
            'toStation' => $toStation,
        ]);
    }

    public static function fineDinePartial(string $deviceUid, $order, $items, $status): self
    {
        return new self($deviceUid, 'FINEDINE_PARTIAL', [
            'order' => $order,
            'items' => $items,
            'status' => $status,
        ]);
    }
}
