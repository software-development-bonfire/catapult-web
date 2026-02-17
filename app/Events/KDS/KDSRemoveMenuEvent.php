<?php

namespace App\Events\KDS;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class KDSRemoveMenuEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $item;
    public $station;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($item, $station)
    {
        $this->item = $item;
        $this->station = $station;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return ['kds-channel'];
    }

    public function broadcastAs()
    {
        return 'kds-remove-menu-event';
    }
}
