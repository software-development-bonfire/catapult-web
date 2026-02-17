<?php

namespace App\Events\KDS;

use App\Enums\POS\EventMessageType;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class KDSReleaseEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $source;
    public $type;
    public $transaction;
    public $items;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($source, $type, $transaction, $items)
    {
        $this->source = $source;
        $this->transaction = $transaction;
        $this->items = $items;
        $this->type = $type;
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
        return 'kds-release-event';
    }
}
