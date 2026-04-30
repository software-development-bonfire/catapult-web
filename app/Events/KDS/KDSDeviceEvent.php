<?php

namespace App\Events\KDS;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Log;

class KDSDeviceEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $device;
    public $transaction;
    public $items;
    public $releasing;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($device, $transaction, $items, $releasing)
    {
        $this->device = $device;
        $this->transaction = $transaction;
        $this->items = $items;
        $this->releasing = $releasing;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Use device_uid to create a private channel specific to each KDS device
        return new PrivateChannel('kds-device-' . $this->device);
    }
    
    public function broadcastAs()
    {
        return 'kds-device-event';
    }
}
