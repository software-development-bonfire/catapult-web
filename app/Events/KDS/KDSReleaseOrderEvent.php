<?php

namespace App\Events\KDS;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class KDSReleaseOrderEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $source;
    public $type;
    public $transaction;
    public $items;

    /**
     * Create a new event instance.
     *
     * @param string $source - The source of the release event
     * @param string $type - The movement type (PER_ORDER, etc)
     * @param object $transaction - The order transaction
     * @param array $items - The items being released
     * @return void
     */
    public function __construct($source, $type, $transaction, $items = [])
    {
        $this->source = $source;
        $this->type = $type;
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

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'kds-release-order-event';
    }
}
