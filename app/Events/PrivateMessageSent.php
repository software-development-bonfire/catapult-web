<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PrivateMessageSent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;
    public $channelName;

    public function __construct($message, $channelName)
    {
        $this->message = $message;
        $this->channelName = $channelName;
    }

    public function broadcastOn()
    {
        return new PrivateChannel($this->channelName);
    }

    public function broadcastAs()
    {
        return 'private-message';
    }
}