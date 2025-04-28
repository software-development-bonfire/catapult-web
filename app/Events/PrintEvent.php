<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PrintEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $device;
    public $content;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($device,  $content)
    {
        $this->device = $device;
        $this->content = $content;
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return ['print-channel'];
    }

    public function broadcastAs()
    {
        return 'print-event';
    }
}
