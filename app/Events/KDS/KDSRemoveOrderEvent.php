<?php

namespace App\Events\KDS;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class KDSRemoveOrderEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $transaction;
    public $items;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($transaction, $items)
    {
        $this->transaction = $transaction;
        $this->items = $items;
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
        return 'kds-remove-order-event';
    }
}
