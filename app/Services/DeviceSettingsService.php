<?php

namespace App\Services;

use App\Entities\DeviceSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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
        $result = DeviceSettings::find($data['bid']);
        $result->update($data);

        return $result;
    }
}