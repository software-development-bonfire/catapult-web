<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// This event is use to broadcast transaction data from KIOSK
// after receiving it, if the transaction from KIOSK is already paid
class TransactionEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $source;
    public $target;
    public $transaction;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($source, $target,  $transaction)
    {
        $this->source = $source;
        $this->target = $target;
        $this->transaction = $transaction;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        //return new Channel('transaction-channel');
        return ['transaction-channel'];
    }

    public function broadcastAs()
    {
        return 'transaction-event';
    }
}
