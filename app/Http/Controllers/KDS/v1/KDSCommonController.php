<?php

namespace App\Http\Controllers\KDS\v1;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\DeviceSettingsRepository;
use App\Repositories\Contracts\KitchenDisplayRepository;
use App\Repositories\Contracts\KitchenStationProcessRepository;
use App\Repositories\Contracts\KitchenStationRepository;
use App\Repositories\Eloquent\KitchenDisplayRepositoryEloquent;
use App\Services\KitchenDisplay\KitchenDisplayFastFoodService;
use App\Services\KitchenDisplay\KitchenDisplayFineDineService;
use App\Transformers\CDIS\KitchenStation\DeviceStationTransformer;
use App\Transformers\CDIS\KitchenStation\ListTransformer;
use App\Transformers\CDIS\KitchenStation\ProcessListTransformer;
use App\Transformers\KDS\KitchenDisplay\AddonListTransformer;
use App\Transformers\KDS\KitchenDisplay\MenuListTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Fractalistic\ArraySerializer;

class KDSCommonController extends Controller
{
    /**
     * Get station list.
     */
    public function getStationList(Request $request): JsonResponse
    {
        $filters = stringToJson($request->get('filters'));

        $stations = app()->make(KitchenStationRepository::class)->list($filters);
        $stations = fractal($stations, ListTransformer::class)->serializeWith(new ArraySerializer());

        return $this->successfulResponse([
            'station' => $stations
        ]);
    }

    /**
     * Get device station info.
     */
    public function getDeviceStation(Request $request): JsonResponse
    {
        $filters = stringToJson($request->get('filters'));

        $stations = app()->make(DeviceSettingsRepository::class)->getKitchenStation($filters);
        $stations = fractal($stations, DeviceStationTransformer::class)->serializeWith(new ArraySerializer());

        return $this->successfulResponse($stations);
    }

    /**
     * Get station process list.
     */
    public function getStationProcessList(Request $request): JsonResponse
    {
        $filters = stringToJson($request->get('filters'));

        $stationProcesses = app()->make(KitchenStationProcessRepository::class)->list($filters);
        $stationProcesses = fractal($stationProcesses, ProcessListTransformer::class)
            ->serializeWith(new ArraySerializer());

        return $this->successfulResponse([
            'station_process' => $stationProcesses
        ]);
    }

    /**
     * Get menu list (shared between order types).
     */
    public function getMenuList(Request $request): JsonResponse
    {
        $filters = stringToJson($request->get('filters'));

        $menus = app()->make(KitchenDisplayRepository::class)->getMenuList($filters);

        foreach ($menus as $menu) {
            $addonFilters = (object) ['transaction_product_bid' => $menu->transaction_product_bid];
            $addons = app()->make(KitchenDisplayRepositoryEloquent::class)->getAddonList($addonFilters);
            $addons = fractal($addons, AddonListTransformer::class)->serializeWith(new ArraySerializer());
            $menu->addon = $addons;
        }

        $menus = fractal($menus, MenuListTransformer::class)->serializeWith(new ArraySerializer());

        return $this->successfulResponse([
            'menu' => $menus
        ]);
    }

    /**
     * Get order details by order_id.
     */
    public function getOrderDetails(Request $request, string $order_id): JsonResponse
    {
        $systemMode = $request->get('system_mode', 'FASTFOOD');

        $service = $systemMode === 'FINEDINE'
            ? app()->make(KitchenDisplayFineDineService::class)
            : app()->make(KitchenDisplayFastFoodService::class);

        $order = $service->getOrderDetails($order_id);

        if (!$order) {
            return $this->errorResponse([], 'Order not found');
        }

        return $this->successfulResponse($order);
    }

    /**
     * Get orders by station index.
     */
    public function getOrdersByStation(Request $request, int $station_index): JsonResponse
    {
        $systemMode = $request->get('system_mode', 'FASTFOOD');

        $service = $systemMode === 'FINEDINE'
            ? app()->make(KitchenDisplayFineDineService::class)
            : app()->make(KitchenDisplayFastFoodService::class);

        $orders = $service->getOrdersByStation($station_index);

        return $this->successfulResponse($orders);
    }
}
