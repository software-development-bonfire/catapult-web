<?php

namespace App\Events\OTS;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// This event is use to broadcast transaction data from POS
// after receiving it, if the transaction from POS is already paid
class OTSSettledEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $tableId;
    public $target;
    public $transaction;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($tableId, $target,  $transaction)
    {
        $this->tableId = $tableId;
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
        return ['station-ots-channel'];
    }

    public function broadcastAs()
    {
        return 'ots-settled-event';
    }
}
