<?php

namespace App\Events\KDS;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class KDSOrderDoneEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $items;
    public $transaction;

    /**
     * Create a new event instance.
     *
     * @param array $items - The menu items being marked as done
     * @param object $transaction - The order transaction
     * @return void
     */
    public function __construct($items, $transaction)
    {
        $this->items = $items;
        $this->transaction = $transaction;
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

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'kds-order-done-event';
    }
}
