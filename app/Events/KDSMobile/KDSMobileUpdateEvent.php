<?php

namespace App\Events\KDSMobile;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event broadcast when kitchen_display or kitchen_display_detail tables are updated.
 * Informs the KDS Mobile Monitor app to fetch/reload data.
 *
 * Channel: private-kds-mobile-monitor-{branchBid}
 * Event name: kds-mobile-update
 */
class KDSMobileUpdateEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $branchBid;
    public string $action;
    public ?string $transactionId;
    public ?string $orderNumber;

    public function __construct(string $branchBid, string $action, ?string $transactionId = null, ?string $orderNumber = null)
    {
        $this->branchBid = $branchBid;
        $this->action = $action;
        $this->transactionId = $transactionId;
        $this->orderNumber = $orderNumber;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('kds-mobile-monitor-' . $this->branchBid);
    }

    public function broadcastAs()
    {
        return 'kds-mobile-update';
    }

    public function broadcastWith(): array
    {
        return [
            'branch_bid' => $this->branchBid,
            'action' => $this->action,
            'transaction_id' => $this->transactionId,
            'order_number' => $this->orderNumber,
            'timestamp' => now()->toISOString(),
        ];
    }
}
