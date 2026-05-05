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
 * Channels:
 *   - kds-transaction-{deviceUid} — transaction events
 *   - kds-station-{deviceUid}     — station movement/release/done/remove events
 *   - kds-command-{deviceUid}     — device command events
 */
abstract class KDSEventBase implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Target device UID - all events are scoped to a specific KDS device.
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
     * Override in subclasses for specific channel prefixes.
     */
    abstract protected function getChannelName(): string;

    /**
     * Get the name the event should broadcast as
     */
    abstract public function broadcastAs();

    /**
     * Get data to broadcast
     */
    abstract public function broadcastWith();
}
