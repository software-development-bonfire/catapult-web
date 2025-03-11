<?php

namespace App\Services\KDS;

use App\Entities\DeviceSettings;
use App\Traits\DatabaseTransaction;

class DeviceSettingsService
{
    use DatabaseTransaction;

    public function updateStatus($data)
    {
        return $this->transaction(function () use ($data) {
            $data = (object) stringToJson($data);
            $deviceSetting = DeviceSettings::where('device_uid', '=', $data->device_uid)->first();
            $fillableData = [
                'device_uid' => $data->device_uid,
                'device_type' => $data->device_type,
                'device_code' => $data->device_code,
                'terminal_code' => $data->terminal_code,
                'name' => $data->device_name,
                'last_connected_at' => now(),
            ];
            if (!empty($data->ip_address)) {
                $fillableData['ip_address'] = $data->ip_address;
            }
            if ($deviceSetting) {
                $deviceSetting = tap($deviceSetting)->update($fillableData);
            } else {
                $deviceSetting = DeviceSettings::create($fillableData);
            }
            return $deviceSetting;
        });
    }
}
