<?php

namespace App\Http\Controllers\KDS\v1;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\DeviceSettingsRepository;
use App\Repositories\Contracts\KitchenStationProcessRepository;
use App\Repositories\Contracts\KitchenStationRepository;
use App\Transformers\CDIS\KitchenStation\DeviceStationTransformer;
use App\Transformers\CDIS\KitchenStation\ListTransformer;
use App\Transformers\CDIS\KitchenStation\ProcessListTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Fractalistic\ArraySerializer;

class KitchenStationController extends Controller
{
    /**
     * Get station list
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse $result
     */
    public function list(Request $request)
    {
        $filters = stringToJson($request->get('filters'));

        $stations = app()->make(KitchenStationRepository::class)
            ->list($filters);

        $stations = fractal($stations, ListTransformer::class)->serializeWith(new ArraySerializer());

        return $this->successfulResponse([
            'station' => $stations
        ]);
    }

    /**
     * Get station process list
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse $result
     */
    public function stationProcessList(Request $request)
    {
        $filters = stringToJson($request->get('filters'));

        $stationProcesses = app()->make(KitchenStationProcessRepository::class)
            ->list($filters);

        $stationProcesses = fractal($stationProcesses, ProcessListTransformer::class)
            ->serializeWith(new ArraySerializer());

        return $this->successfulResponse([
            'station_process' => $stationProcesses
        ]);
    }

     /**
     * Get station list
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse $result
     */
    public function device(Request $request)
    {
        $filters = stringToJson($request->get('filters'));

        $stations = app()->make(DeviceSettingsRepository::class)->getKitchenStation($filters);

        $stations = fractal($stations, DeviceStationTransformer::class)->serializeWith(new ArraySerializer());


        return $this->successfulResponse($stations);
    }

}
