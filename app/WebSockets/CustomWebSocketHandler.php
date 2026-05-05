<?php

namespace App\WebSockets;

use App\Enums\API\DeviceType;
use App\Services\KDS\DeviceSettingsService as KDSDeviceSettingsService;
use App\Services\POS\DeviceSettingsService as POSDeviceSettingsService;
use App\Services\KIOSK\DeviceSettingsService as KIOSKDeviceSettingsService;
use Ratchet\ConnectionInterface;
use BeyondCode\LaravelWebSockets\WebSockets\WebSocketHandler as BaseWebSocketHandler;

class CustomWebSocketHandler extends BaseWebSocketHandler
{
    protected $connections = [];

    public function onOpen(ConnectionInterface $connection)
    {
        // Set as initial data by storing connection metadata
        $this->connections[$connection->resourceId] = $connection;

        parent::onOpen($connection);
    }

    public function onClose(ConnectionInterface $connection)
    {
        // Handle device disconnection
        $deviceInfo = $this->connections[$connection->resourceId] ?? null;

        if ($deviceInfo && isset($deviceInfo->device_type)) {
            $deviceInfo->socket_status = 0; // Set to OFFLINE if device disconnected
            $this->handleSocketStatus($deviceInfo);
        }
        unset($this->connections[$connection->resourceId]);

        parent::onClose($connection);
    }

    public function onMessage(ConnectionInterface $connection, $message)
    {
        // Decode the incoming message
        $data = json_decode($message, true);

        // Log the message for debugging
        //\Illuminate\Support\Facades\Log::info('Received message:', $data);

        $this->handleReceivedMessage($connection, $data);

        parent::onMessage($connection, $message);
    }

    function handleReceivedMessage(ConnectionInterface $connection, $data)
    {
        $data = (object) $data;
        if (isset($data->event)) {
            // If event is not null and equals to the defined event name
            // then we updates status of the device connection
            if ($data->event == 'pusher:ping' || $data->event == 'client-my-event') {
                $deviceInfo = (object) stringToJson($data->data);

                // Store device info to track during onClose
                $this->connections[$connection->resourceId] = $deviceInfo;

                $deviceInfo->socket_status = 1; // Automatically set to online socket status if there is a message from device

                $this->handleSocketStatus($deviceInfo);
            }
        }
    }

    function handleSocketStatus($deviceInfo)
    {
        if ($deviceInfo->device_type == DeviceType::KDS) {
            app()->make(KDSDeviceSettingsService::class)->updateStatus($deviceInfo);
        } elseif ($deviceInfo->device_type == DeviceType::SIRIUS_POS) {
            app()->make(POSDeviceSettingsService::class)->updateStatus($deviceInfo);
        } elseif ($deviceInfo->device_type == DeviceType::KIOSK) {
            app()->make(KIOSKDeviceSettingsService::class)->updateStatus($deviceInfo);
        } else {
            //@TODO: handle here other device type
        }
    }
}
