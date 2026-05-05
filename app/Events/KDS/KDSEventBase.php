<?php

namespace App\Events\KDS;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base KDS Event
 * 
 * All KDS events extend this for common functionality.
 * 
 * Channels (private, device-scoped):
 *   kds-transaction-{deviceUid} → broadcast as "kds-transaction-event"
 *   kds-station-{deviceUid}     → broadcast as "kds-station-event"
 *   kds-command-{deviceUid}     → broadcast as "command-event"
 * 
 * Payload routing fields:
 *   mode   — "finedine" | "fastfood"
 *   entity — "menu" | "order" | "item"        (station events only)
 *   action — "release" | "move" | "remove" | "done"  (station events only)
 */
abstract class KDSEventBase implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Target device UID — all events are scoped to a specific KDS device.
     */
    public $deviceUid = '';

    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn()
    {
        return new PrivateChannel($this->getChannelName());
    }

    /**
     * Get the channel name for this event.
     */
    abstract protected function getChannelName(): string;

    /**
     * Get the name the event should broadcast as.
     * 
     * Station and transaction subclasses share a unified broadcast name
     * so the client only needs one bind per channel.
     */
    abstract public function broadcastAs();

    /**
     * Get data to broadcast.
     */
    abstract public function broadcastWith();
}
