<?php

namespace App\Events\KDS;

use App\Enums\POS\EventMessageType;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class KDSTransactionEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $device;
    public $transaction;
    public $items;
    public $releasing;
    public $type;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($device, $transaction, $items, $releasing, $type)
    {
        $this->device = $device;
        $this->transaction = $transaction;
        $this->items = $items;
        $this->releasing = $releasing;
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
        return 'kds-event';
    }
}
