<?php

namespace App\Events\KDS;

use App\Enums\POS\EventMessageType;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class KDSMoveEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $source;
    public $target;
    public $type;
    public $transaction;
    public $items;
    public $direction;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($source, $target, $type, $transaction, $items, $direction)
    {
        $this->source = $source;
        $this->target = $target;
        $this->transaction = $transaction;
        $this->items = $items;
        $this->type = $type;
        $this->direction = $direction;
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
        return 'kds-move-event';
    }
}
