<?php

namespace App\Services\KDS;

use App\Entities\DeviceSettings;
use App\Enums\API\DeviceType;
use App\Enums\Status;
use App\Traits\DatabaseTransaction;

class DeviceSettingsService
{
    use DatabaseTransaction;

    public function update($data)
    {
        return $this->transaction(function () use ($data) {
            $data = (object) stringToJson($data);

            // Update all devices that haven't connected in the last 5 minutes
            DeviceSettings::where('last_connected_at', '<', now()->subMinutes(5))
                ->update(['socket_status' => 0]);

            // If request contains "old" and "new" structure
            if (isset($data->old) && isset($data->new)) {
                $old = (object) $data->old;
                $new = (object) $data->new;

                // Find old device record
                $deviceSetting = DeviceSettings::where('device_uid', $old->device_uid)->first();

                if ($deviceSetting) {
                    // Update old device with new details
                    $updateData = [
                        'device_uid'       => $new->device_uid,
                        'device_code'      => $new->device_code,
                        'name'             => $new->friendly_name ?? $new->device_name,
                        'last_connected_at' => now(),
                        'socket_status'    => 1, // assume connected after update
                    ];

                    $deviceSetting->update($updateData);
                } else {
                    // If old device not found, just create new one
                    $deviceSetting = DeviceSettings::create([
                        'device_uid'    => $new->device_uid,
                        'device_type'   => $new->device_type ?? DeviceType::KDS,
                        'device_code'   => $new->device_code,
                        'terminal_code' => $new->terminal_code ?? null,
                        'name'          => $new->friendly_name ?? $new->device_name,
                        'ip_address'    => $new->ip_address ?? null,
                        'status'        => $new->status ?? Status::ACTIVE,
                        'last_connected_at' => now(),
                        'socket_status' => 1,
                    ]);
                }

                return $deviceSetting;
            }

            // If only single device data is sent (backward compatible with old request)
            $deviceSetting = DeviceSettings::where('device_uid', $data->device_uid)->first();

            if ($deviceSetting) {
                $updateData = [
                    'socket_status' => $data->socket_status ?? 1,
                    'last_connected_at' => now()
                ];
                if (!empty($data->ip_address)) {
                    $updateData['ip_address'] = $data->ip_address;
                }
                $deviceSetting->update($updateData);
            } else {
                $deviceSetting = DeviceSettings::create([
                    'device_uid'    => $data->device_uid,
                    'device_type'   => $data->device_type,
                    'device_code'   => $data->device_code,
                    'terminal_code' => $data->terminal_code ?? null,
                    'name'          => $data->name ?? ($data->device_name ?? ''),
                    'ip_address'    => $data->ip_address ?? null,
                    'status'        => $data->status ?? Status::ACTIVE,
                    'last_connected_at' => now(),
                    'socket_status' => 1,
                ]);
            }

            return $deviceSetting;
        });
    }


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
