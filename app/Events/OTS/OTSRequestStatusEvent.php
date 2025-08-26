<?php

namespace App\Events\OTS;

use BeyondCode\LaravelWebSockets\Server\Logger\Logger;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OTSRequestStatusEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;
    public $requestType;
    public $data;
    public $deviceID;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($requestType, $deviceID, $data)
    {
        $this->requestType = $requestType;
        $this->deviceID = $deviceID;
        $this->data = $data;
        Log::info("Broadcasting OTSRequestStatusEvent: Type - {$requestType}, DeviceID - {$deviceID}");
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('ots-request-status-channel');
    }

    public function broadCastAs()
    {
        return 'ots-request-event';
    }
}
