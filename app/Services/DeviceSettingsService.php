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
        $result = DeviceSettings::find($data['bid'])->update($data);

        return $result;
    }

    /**
     * Destroy data
     *
     * @param string $bid
     */
    public function destroy($bid)
    {
        $data = DeviceSettings::findOrFail($bid);
        $data->delete();
        return $data;
    }
}