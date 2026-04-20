<?php

namespace App\Http\Controllers\KDS\v1;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\KitchenDisplayRepository;
use App\Repositories\Eloquent\KitchenDisplayRepositoryEloquent;
use App\Services\KitchenDisplay\KitchenDisplayFastFoodService;
use App\Transformers\KDS\KitchenDisplay\AddonListTransformer;
use App\Transformers\KDS\KitchenDisplay\MenuListTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Fractalistic\ArraySerializer;

class KDSFastFoodController extends Controller
{
    protected $service;

    public function __construct(KitchenDisplayFastFoodService $service)
    {
        $this->service = $service;
    }

    /**
     * Get fast-food order list.
     */
    public function getOrderList(Request $request): JsonResponse
    {
        $stationIndex = $request->get('station_index');

        if ($stationIndex) {
            $orders = $this->service->getOrdersByStation((int) $stationIndex);
        } else {
            $orders = $this->service->getOrdersByStation(0); // all
        }

        return $this->successfulResponse($orders);
    }

    /**
     * Get single order details.
     */
    public function getOrder(Request $request, string $order_id): JsonResponse
    {
        $order = $this->service->getOrderDetails($order_id);

        if (!$order) {
            return $this->errorResponse([], 'Order not found');
        }

        return $this->successfulResponse($order);
    }

    /**
     * Get menu list.
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

        return $this->successfulResponse(['menu' => $menus]);
    }

    /**
     * Move menu to next/previous station.
     */
    public function moveMenu(Request $request): JsonResponse
    {
        try {
            $result = $this->service->moveItem($request->all());

            if (!$result) {
                return $this->errorResponse([], 'Failed to move menu');
            }
        } catch (\Exception $ex) {
            return $this->errorResponse([], $ex->getMessage());
        }

        return $this->successfulResponse($result);
    }

    /**
     * Release menu (mark ready for pickup).
     */
    public function releaseMenu(Request $request): JsonResponse
    {
        try {
            $result = $this->service->releaseItem($request->all());

            if (!$result) {
                return $this->errorResponse([], 'Failed to release menu');
            }
        } catch (\Exception $ex) {
            return $this->errorResponse([], $ex->getMessage());
        }

        return $this->successfulResponse($result);
    }

    /**
     * Done menu (mark item as completed).
     */
    public function doneMenu(Request $request): JsonResponse
    {
        try {
            $result = $this->service->doneItem($request->all());

            if (!$result) {
                return $this->errorResponse([], 'Failed to mark menu as done');
            }
        } catch (\Exception $ex) {
            return $this->errorResponse([], $ex->getMessage());
        }

        return $this->successfulResponse($result);
    }

    /**
     * Remove menu item.
     */
    public function removeMenu(Request $request): JsonResponse
    {
        try {
            $result = $this->service->removeItem($request->all());

            if (!$result) {
                return $this->errorResponse([]);
            }
        } catch (\Exception $ex) {
            return $this->errorResponse([]);
        }

        return $this->successfulResponse();
    }

    /**
     * Move entire order to next/previous station.
     */
    public function moveOrder(Request $request): JsonResponse
    {
        $result = $this->service->moveItem($request->all());

        return $this->successfulResponse($result);
    }

    /**
     * Release entire order.
     */
    public function releaseOrder(Request $request): JsonResponse
    {
        $result = $this->service->releaseItem($request->all());

        return $this->successfulResponse($result);
    }

    /**
     * Done entire order.
     */
    public function doneOrder(Request $request): JsonResponse
    {
        $result = $this->service->doneOrder($request->all());

        return $this->successfulResponse($result);
    }

    /**
     * Remove entire order.
     */
    public function removeOrder(Request $request): JsonResponse
    {
        try {
            $result = $this->service->removeOrder($request->all());

            if (!$result) {
                return $this->errorResponse([]);
            }
        } catch (\Exception $ex) {
            return $this->errorResponse([]);
        }

        return $this->successfulResponse();
    }

    /**
     * Move individual item (row-level).
     */
    public function moveItem(Request $request): JsonResponse
    {
        try {
            $result = $this->service->moveItem($request->all());

            if (!$result) {
                return $this->errorResponse([]);
            }
        } catch (\Exception $ex) {
            return $this->errorResponse([]);
        }

        return $this->successfulResponse($result);
    }
}
