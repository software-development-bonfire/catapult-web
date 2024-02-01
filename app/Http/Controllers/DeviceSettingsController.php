<?php

namespace App\Http\Controllers;

use App\Entities\DeviceSettings;
use App\Http\Requests\DeviceSettingsRequest;
use App\Repositories\Contracts\DeviceSettingsRepository;
use App\Services\DeviceSettingsService;
use App\Transformers\DeviceSettingsTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class DeviceSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $isForHeader = false)
    {
        $list = app()->make(DeviceSettingsRepository::class)->list($request->all(), $isForHeader);
        $list = fractal($list, DeviceSettingsTransformer::class);

        return $this->successfulResponse($list);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  DeviceSettingsRequest $request
     * @return JsonResponse
     */
    public function store(DeviceSettingsRequest $request)
    {
        try {
            $data = app()->make(DeviceSettingsService::class)->store($request->all());
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.failed_to_insert_the_data')
            );
        }
        return $this->successfulResponse(
            $data,
            Lang::get('success.successfully_created', ['value' => __('label.device_settings')])
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  DeviceSettingsRequest  $request
     * @param  string  $bid
     * @return \Illuminate\Http\Response
     */
    public function update(DeviceSettingsRequest $request)
    {
        try {
            $data = app()->make(DeviceSettingsService::class)->update($request->all());
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.failed_to_update_the_data')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.value_successfully_updated', ['value' => __('label.device_settings')])
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $bid
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeviceSettingsRequest $request)
    {
        try {
            $data = app()->make(DeviceSettingsService::class)->destroy($request->get('bid'));
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.failed_to_delete_the_data')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.value_successfully_deleted', ['value' => __('label.device_settings')])
        );
    }

}
