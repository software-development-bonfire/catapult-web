<?php

namespace App\Services;

use App\Entities\DeviceSettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DeviceSettingsService
{
    /**
     * Store data
     *
     * @param array $data
     */
    public function store($data)
    {
        $userBid = Auth::user()->bid;
        $data['created_by'] = $userBid;
        $value = (object) $data;

        $result = DeviceSettings::create([
            'device_type' => $value->device_type,
            'name' => $value->name,
            'ip_address' => $value->ip_address,
            'api_endpoint' => $value->api_endpoint,
            'token' => $value->token,
            'status' => $value->status,
            'created_by' => $userBid
        ]);

        return $result;
    }

    /**
     * Update data
     *
     * @param array $data
     * @param string $bid
     */
    public function update($data)
    {
        $data['updated_by'] = Auth::user()->bid;
        return DeviceSettings::find($data['bid'])->update($data);
    }

    /**
     * Destroy data
     *
     * @param string $bid
     */
    public function destroy($bid)
    {
        DeviceSettings::find($bid)->delete();
        return true;
    }

    /**
     * Permanently delete devices not connected for more than 5 days
     *
     * @return int Number of deleted records
     */
    public function purgeInactiveDevices(): int
    {
        $threshold = Carbon::now()->subDays(5);

        return DeviceSettings::where('last_connected', '<', $threshold)
            ->forceDelete(); // use delete() if SoftDeletes is NOT used
    }

    public function setToOffline($minutes = 5)
    {
        // Update all devices that haven't connected in the last 5 minutes
        DeviceSettings::where('last_connected_at', '<', now()->subMinutes($minutes))
            ->update(['socket_status' => 0]);
    }
}
