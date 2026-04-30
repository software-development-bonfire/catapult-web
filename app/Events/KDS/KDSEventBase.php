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
 * All KDS events extend this for common functionality
 */
abstract class KDSEventBase implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Event type identifier (e.g. FASTFOOD_ORDER, FINEDINE_RELEASE)
     * The event class and eventType already encode the system mode.
     */
    protected $eventType = 'UNKNOWN';

    /**
     * Target device UID - all events are scoped to a specific KDS device.
     * If received, it's intended for this device. No extra validation needed.
     */
    public $deviceUid = '';

    /**
     * Get the channels the event should broadcast on
     * 
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel($this->getChannelName());
    }

    /**
     * Get the channel name for this event.
     * Override in subclasses for mode-specific channels.
     */
    protected function getChannelName(): string
    {
        return 'kds-device-' . $this->deviceUid;
    }

    /**
     * Get the name the event should broadcast as
     * 
     * @return string
     */
    abstract public function broadcastAs();

    /**
     * Get data to broadcast
     * 
     * @return array
     */
    abstract public function broadcastWith();
}
