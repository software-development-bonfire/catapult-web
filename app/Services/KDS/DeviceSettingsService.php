<?php

namespace App\Services\KDS;

use App\Entities\DeviceSettings;
use App\Enums\API\DeviceType;
use App\Enums\Status;
use App\Traits\DatabaseTransaction;

class DeviceSettingsService
{
    use DatabaseTransaction;

    public function updateStatus($data)
    {
        return $this->transaction(function () use ($data) {
            $data = (object) stringToJson($data);

            // Update all devices that haven't connected in the last 5 minutes
            DeviceSettings::where('last_connected_at', '<', now()->subMinutes(5))
                ->update(['socket_status' => 0]);

            // Check if the device already exists
            $deviceSetting = DeviceSettings::where('device_uid', $data->device_uid)->first();

            if ($deviceSetting) {
                $updateData = [
                    'socket_status' => $data->socket_status,
                    'last_connected_at' => now()
                ];
                if (!empty($data->ip_address)) {
                    $updateData['ip_address'] = $data->ip_address;
                }
                $deviceSetting = tap($deviceSetting)->update($updateData);
            } else {
                $deviceSetting = DeviceSettings::create([
                    'device_uid' => $data->device_uid,
                    'device_type' => $data->device_type,
                    'device_code' => $data->device_code,
                    'terminal_code' => $data->terminal_code,
                    'name' => isset($data->name) ? $data->name : (isset($data->device_name) ? $data->device_name : ''),
                    'ip_address' => $data->ip_address,
                    'status' => isset($data->status) ? $data->status : Status::ACTIVE,
                ]);
            }
            return $deviceSetting;
        });
    }
}
