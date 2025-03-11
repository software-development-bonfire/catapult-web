<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MyPresenceEvent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;
    public $roomId;

    public function __construct($roomId, $message)
    {
        $this->roomId = $roomId;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('chat.' . $this->roomId);
    }
}
