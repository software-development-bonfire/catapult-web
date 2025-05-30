<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ComputeEngine implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;
    public $data;
    public $deviceID;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($data, $deviceID)
    {
        $this->data = $data;
        $this->deviceID = $deviceID;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {   
        // $deviceID = $this->deviceID;
        // $channel = 'sirius-compute-engine-'.$deviceID;
        return new PrivateChannel('sirius-compute-engine');
    }

    public function broadCastAs()
    {
        return 'compute-event';
    }
}
