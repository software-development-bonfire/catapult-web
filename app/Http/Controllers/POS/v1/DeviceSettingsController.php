<?php

namespace App\Http\Controllers\POS\v1;

use App\Entities\DeviceSettings;
use App\Events\DeviceStatusEvent;
use App\Http\Controllers\POS\POSBaseController;
use App\Services\POS\DeviceSettingsService;
use App\Transformers\DeviceSettingsTransformer;
use Illuminate\Http\Request;

class DeviceSettingsController extends POSBaseController
{
    public function list(Request $request)
    {
        $list = DeviceSettings::get();
        $list = fractal($list, DeviceSettingsTransformer::class);

        return $this->successfulResponse($list->toArray()['data']);
    }

    public function deviceStatus(Request $request)
    {
        $data = (object) stringToJson($request->all());
        $result = app()->make(DeviceSettingsService::class)->updateStatus($data);
      
        $result = fractal($result, DeviceSettingsTransformer::class);
        $result = $result->toArray()['data'];

        broadcast(new DeviceStatusEvent($result));

        return $this->successfulResponse($result, 'Device status broadcasted successfully!');
    }

    public function devicePrintStatus(Request $request)
    {
        $data = (object) stringToJson($request->all());

        $result = app()->make(DeviceSettingsService::class)->updatePrintStatus($data);
        
        $result = fractal($result, DeviceSettingsTransformer::class);
        $result = $result->toArray()['data'];

        broadcast(new DeviceStatusEvent($result));

        return $this->successfulResponse($result, 'Printer status broadcasted successfully!');
    }
}
