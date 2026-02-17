<?php

namespace App\Events\KDS;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class KDSMoveOrderEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $transaction;
    public $items;
    public $fromStation;
    public $toStation;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($transaction, $items, $fromStation, $toStation)
    {
        $this->transaction = $transaction;
        $this->items = $items;
        $this->fromStation = $fromStation;
        $this->toStation = $toStation;
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
        return 'kds-move-order-event';
    }
}
