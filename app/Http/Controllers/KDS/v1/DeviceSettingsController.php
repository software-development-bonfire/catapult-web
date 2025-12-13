<?php

namespace App\Http\Controllers\KDS\v1;

use App\Entities\DeviceSettings;
use App\Events\DeviceStatusEvent;
use App\Http\Controllers\KDS\KDSBaseController;
use App\Services\KDS\DeviceSettingsService;
use App\Transformers\DeviceSettingsTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceSettingsController extends KDSBaseController
{
    public function list(Request $request)
    {
        // Update all devices that haven't connected in the last 5 minutes
        // If there are devices request to get the list of devices
        DeviceSettings::where('last_connected_at', '<', now()->subMinutes(5))->update(['socket_status' => 0]);

        $list = DeviceSettings::get();
        $list = fractal($list, DeviceSettingsTransformer::class);

        return $this->successfulResponse($list->toArray()['data']);
    }
    
    public function update(Request $request)
    {
        $data = (object) stringToJson($request->all());

        $result = app()->make(DeviceSettingsService::class)->update($data);

        $result = fractal($result, DeviceSettingsTransformer::class);
        $result = $result->toArray()['data'];


        return $this->successfulResponse($result, 'Device updated successfully!');
    }

    public function status(Request $request)
    {
        $data = (object) stringToJson($request->all());
        $result = app()->make(DeviceSettingsService::class)->updateStatus($data);

        $result = fractal($result, DeviceSettingsTransformer::class);
        $result = $result->toArray()['data'];

        broadcast(new DeviceStatusEvent($result));

        return $this->successfulResponse($result, 'Device status broadcasted successfully!');
    }
}
