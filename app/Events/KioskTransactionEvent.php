<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KioskTransactionEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transaction = '';
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($message)
    {
        $this->transaction = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return ['kiosk-transaction'];
    }

    public function broadcastAs()
    {
        return 'kiosk-transaction-event';
    }
}
