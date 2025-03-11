<?php

namespace App\WebSockets;

use App\Services\KDS\DeviceSettingsService;
use Ratchet\ConnectionInterface;
use BeyondCode\LaravelWebSockets\WebSockets\WebSocketHandler as BaseWebSocketHandler;

class CustomWebSocketHandler extends BaseWebSocketHandler
{
    public function onMessage(ConnectionInterface $connection, $message)
    {
        // Decode the incoming message
        $data = json_decode($message, true);

        // Log the message for debugging
        \Illuminate\Support\Facades\Log::info('Received message:', $data);

        $this->handleReceivedMessage($data);

        // Continue processing as usual
        parent::onMessage($connection, $message);
    }

    function handleReceivedMessage($data)
    {
        $data = (object) $data;
        if (isset($data->event)) {
            if ($data->event == 'pusher:ping' || $data->event == 'client-my-event') {
                $dataValue = (object) stringToJson($data->data);
                $dataValue->socket_status = 1;
                $dataValue->status = 1;
                app()->make(DeviceSettingsService::class)->updateStatus($dataValue);

                //broadcast(new DeviceStatusEvent($result));
            }
        }
    }
}
