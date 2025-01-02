<?php

namespace App\Services\KIOSK;

use App\Entities\DeviceSettings;
use App\Traits\DatabaseTransaction;

class DeviceSettingsService
{
    use DatabaseTransaction;

    public function updateStatus($data)
    {
        return $this->transaction(function () use ($data) {
            $data = (object) stringToJson($data);
            $deviceSetting = DeviceSettings::where('device_code', '=', $data->device_code)
                ->where('device_type', $data->device_type)
                ->where('terminal_code', $data->terminal_code)
                ->first();

            if ($deviceSetting) {
                $updateData = [
                    'socket_status' => $data->status,
                    'last_connected_at' => now()
                ];
                if (!empty($data->ip_address)) {
                    $updateData['ip_address'] = $data->ip_address;
                }
                $deviceSetting = tap($deviceSetting)->update($updateData);
            } else {
                $deviceSetting = DeviceSettings::create([
                    'device_type' => $data->device_type,
                    'device_code' => $data->device_code,
                    'terminal_code' => $data->terminal_code,
                    'name' => $data->name,
                    'ip_address' => $data->ip_address,
                    'status' => $data->status,
                    ]);
            }
            return $deviceSetting;
        });
    }

    public function updatePrintStatus($data)
    {
        return $this->transaction(function () use ($data) {
            $data = (object) stringToJson($data);
            $deviceSetting = DeviceSettings::where('device_code', '=', $data->device_code)
                ->where('device_type', $data->device_type)
                ->where('terminal_code', $data->terminal_code)
                ->first();

            if ($deviceSetting) {
                $deviceSetting = tap($deviceSetting)->update([
                    'print_invoice' => $data->print_invoice,
                ]);
            }
            return $deviceSetting;
        });
    }
}
