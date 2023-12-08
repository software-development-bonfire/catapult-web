<?php

namespace App\Http\Controllers;

use App\Entities\DeviceSettings;
use App\Repositories\Contracts\DeviceSettingsRepository;
use App\Transformers\DeviceSettingsTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Enums\DeviceType;

class ItemAvailabilityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $header = app()->make(DeviceSettingsRepository::class)->list([], true);
        $header = $this->getTerminals($header);
        $header = json_encode($header);

        return view('item-availability.list', compact(
            'header'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    public function getTerminals($data)
    {
        $header = [];

        foreach ($data as $item) {
            $item = (object) $item;
            $filters = (object) ['device_type' => $item->device_type];
            $terminal = app()->make(DeviceSettingsRepository::class)->list($filters, false)->toArray();

            $header[] = [
                'name' => $item->device_type,
                'terminals' => $terminal
            ];
        }

        return $header;

    }
}
