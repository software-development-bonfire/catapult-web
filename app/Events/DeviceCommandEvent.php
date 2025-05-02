<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Triggered on device command
 */
class DeviceCommandEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $device;
    public $command;
    public $data;
    
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($device, $command, $data)
    {
        $this->device = $device;
        $this->command = $command;
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return ['command-channel'];
    }

    public function broadcastAs()
    {
        return 'command-event';
    }
}
