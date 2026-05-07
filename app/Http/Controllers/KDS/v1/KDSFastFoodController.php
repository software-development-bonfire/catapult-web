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
            $orders = $this->service->getOrdersByStation(0);
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
     * Unified action endpoint for fast-food mode.
     *
     * Body: { "action": "move_order|done_order|release_order|remove_order|move_menu|done_menu|release_menu|remove_menu|move_item", "payload": {...} }
     */
    public function action(Request $request): JsonResponse
    {
        $action = $request->get('action');
        $payload = $request->get('payload', []);

        if (!$action) {
            return $this->errorResponse([], 'action is required');
        }

        try {
            switch ($action) {
                case 'move_order':
                case 'move_menu':
                case 'move_item':
                    $result = $this->service->moveItem($payload);
                    break;
                case 'done_order':
                    $result = $this->service->doneOrder($payload);
                    break;
                case 'done_menu':
                    $result = $this->service->doneItem($payload);
                    break;
                case 'release_order':
                case 'release_menu':
                    $result = $this->service->releaseItem($payload);
                    break;
                case 'remove_order':
                    $result = $this->service->removeOrder($payload);
                    break;
                case 'remove_menu':
                    $result = $this->service->removeItem($payload);
                    break;
                default:
                    $result = null;
                    break;
            }

            if ($result === null) {
                return $this->errorResponse([], "Invalid action: $action");
            }

            if ($result === false) {
                return $this->errorResponse([], "Failed to execute action: $action");
            }

            return $this->successfulResponse(is_bool($result) ? [] : $result);
        } catch (\Exception $ex) {
            return $this->errorResponse([], $ex->getMessage());
        }
    }
}
