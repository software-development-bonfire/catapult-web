<?php

namespace App\Http\Controllers\KDS\v1;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\KitchenDisplayRepository;
use App\Repositories\Eloquent\KitchenDisplayRepositoryEloquent;
use App\Services\KitchenDisplayService;
use App\Transformers\KDS\KitchenDisplay\AddonListTransformer;
use App\Transformers\KDS\KitchenDisplay\MenuListTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Fractalistic\ArraySerializer;

class KitchenDisplayController extends Controller
{
    public function storeMenu(Request $request) {

    }

    public function storeOrder(Request $request) {
        
    }

    /**
     * Get menu list.
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse $result
     */
    public function getMenuList(Request $request)
    {
        $filters = stringToJson($request->get('filters'));

        $menus = app()->make(KitchenDisplayRepository::class)
            ->getMenuList($filters);

        foreach ($menus as $menu) {
            $addonFilters = (object) array(
                'transaction_product_bid' => $menu->transaction_product_bid
            );

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
     * Move menu to other station.
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse $result
     */
    public function moveMenu(Request $request)
    {
        try {
            $moveMenu = app()->make(KitchenDisplayService::class)->moveMenu($request->all());

            if (! $moveMenu) {
                return $this->errorResponse([]);
            }
        } catch (\Exception $ex) {
            return $this->errorResponse([]);
        }

        return $this->successfulResponse();
    }

    /**
     * Remove order in kitchen display.
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse $result
     */
    public function removeOrder(Request $request)
    {
        try {
            $removeOrder = app()->make(KitchenDisplayService::class)->removeOrder($request->all());

            if (! $removeOrder) {
                return $this->errorResponse([]);
            }
        } catch (\Exception $ex) {
            return $this->errorResponse([]);
        }

        return $this->successfulResponse();
    }

    /**
     * Remove menu in kitchen display.
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse $result
     */
    public function removeMenu(Request $request)
    {
        try {
            $removeMenu = app()->make(KitchenDisplayService::class)->removeMenu($request->all());

            if (! $removeMenu) {
                return $this->errorResponse([]);
            }
        } catch (\Exception $ex) {
            return $this->errorResponse([]);
        }

        return $this->successfulResponse();
    }
}
