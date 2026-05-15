<?php

namespace App\Http\Controllers\KDS\v1;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\KitchenDisplayRepository;
use App\Repositories\Eloquent\KitchenDisplayRepositoryEloquent;
use App\Services\KitchenDisplay\KitchenDisplayMovementService;
use App\Transformers\KDS\KitchenDisplay\AddonListTransformer;
use App\Transformers\KDS\KitchenDisplay\MenuListTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Fractalistic\ArraySerializer;

class KitchenDisplayController extends Controller
{
    protected $movementService;

    public function __construct(KitchenDisplayMovementService $movementService)
    {
        $this->movementService = $movementService;
    }

    /**
     * Unified action endpoint for all KDS movement operations.
     *
     * Accepts standardized payload:
     * {
     *   "action": "move_order|move_item|done_order|done_menu|release_order|release_menu|remove_order|remove_menu",
     *   "payload": {
     *     "data": { "items": [], "transaction": {}, "order_type": {} },
     *     "next": true,
     *     "quantity": 0,
     *     "remaining_quantity": 0,
     *     "moved_quantity": 0,
     *     "release": false
     *   }
     * }
     */
    public function action(Request $request): JsonResponse
    {
        $action = $request->get('action');
        $payload = $request->get('payload', []);

        Log::info('KitchenDisplayController::action', ['action' => $action, 'payload' => $payload]);

        if (!$action) {
            return $this->errorResponse([], 'action is required');
        }

        if (empty($payload)) {
            return $this->errorResponse([], 'payload is required');
        }

       // try {
            $result = $this->dispatchAction($action, $payload);

            if (!$result['success']) {
                return $this->errorResponse([], $result['message']);
            }

            return $this->successfulResponse($result['data'], $result['message']);
        /*} catch (\Exception $ex) {
            Log::error('KitchenDisplayController::action error', [
                'action' => $action,
                'error' => $ex->getMessage(),
            ]);
            return $this->errorResponse([], $ex->getMessage());
        }*/
    }

    /**
     * Dispatch action to appropriate service method.
     */
    private function dispatchAction(string $action, array $payload): array
    {
        switch ($action) {
            case 'move_order':
                return $this->movementService->moveOrder($payload);

            case 'move_item':
            case 'move_menu':
                return $this->movementService->moveItem($payload);

            case 'done_order':
                return $this->movementService->doneOrder($payload);

            case 'done_menu':
            case 'done_item':
                return $this->movementService->doneItem($payload);

            case 'release_order':
                return $this->movementService->releaseOrder($payload);

            case 'release_menu':
            case 'release_item':
                return $this->movementService->releaseItem($payload);

            case 'remove_order':
                return $this->movementService->removeOrder($payload);

            case 'remove_menu':
            case 'remove_item':
                return $this->movementService->removeItem($payload);

            default:
                return [
                    'success' => false,
                    'message' => "Invalid action: $action",
                    'data' => null,
                ];
        }
    }

    /**
     * Move item endpoint (POST /api/kds/move-item).
     * Shortcut endpoint that calls move_item action directly.
     */
    public function moveItem(Request $request): JsonResponse
    {
        $payload = $request->all();

        // If payload comes without action wrapper, wrap it
        if (!isset($payload['action'])) {
            $payload = [
                'action' => 'move_item',
                'payload' => $payload,
            ];
        }

        $action = $payload['action'] ?? 'move_item';
        $actionPayload = $payload['payload'] ?? $payload;

        try {
            $result = $this->movementService->moveItem($actionPayload);

            if (!$result['success']) {
                return $this->errorResponse([], $result['message']);
            }

            return $this->successfulResponse($result['data'], $result['message']);
        } catch (\Exception $ex) {
            Log::error('KitchenDisplayController::moveItem error', ['error' => $ex->getMessage()]);
            return $this->errorResponse([], $ex->getMessage());
        }
    }

    /**
     * Move order endpoint.
     * Shortcut endpoint that calls move_order action directly.
     */
    public function moveOrder(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (!isset($payload['action'])) {
            $payload = [
                'action' => 'move_order',
                'payload' => $payload,
            ];
        }

        $actionPayload = $payload['payload'] ?? $payload;

        try {
            $result = $this->movementService->moveOrder($actionPayload);

            if (!$result['success']) {
                return $this->errorResponse([], $result['message']);
            }

            return $this->successfulResponse($result['data'], $result['message']);
        } catch (\Exception $ex) {
            Log::error('KitchenDisplayController::moveOrder error', ['error' => $ex->getMessage()]);
            return $this->errorResponse([], $ex->getMessage());
        }
    }

    /**
     * Release order endpoint.
     */
    public function releaseOrder(Request $request): JsonResponse
    {
        $payload = $request->get('payload', $request->all());

        try {
            $result = $this->movementService->releaseOrder($payload);

            if (!$result['success']) {
                return $this->errorResponse([], $result['message']);
            }

            return $this->successfulResponse($result['data'], $result['message']);
        } catch (\Exception $ex) {
            return $this->errorResponse([], $ex->getMessage());
        }
    }

    /**
     * Release menu endpoint.
     */
    public function releaseMenu(Request $request): JsonResponse
    {
        $payload = $request->get('payload', $request->all());

        try {
            $result = $this->movementService->releaseItem($payload);

            if (!$result['success']) {
                return $this->errorResponse([], $result['message']);
            }

            return $this->successfulResponse($result['data'], $result['message']);
        } catch (\Exception $ex) {
            return $this->errorResponse([], $ex->getMessage());
        }
    }

    /**
     * Done order endpoint.
     */
    public function doneOrder(Request $request): JsonResponse
    {
        $payload = $request->get('payload', $request->all());

        try {
            $result = $this->movementService->doneOrder($payload);

            if (!$result['success']) {
                return $this->errorResponse([], $result['message']);
            }

            return $this->successfulResponse($result['data'], $result['message']);
        } catch (\Exception $ex) {
            return $this->errorResponse([], $ex->getMessage());
        }
    }

    /**
     * Done menu endpoint.
     */
    public function doneMenu(Request $request): JsonResponse
    {
        $payload = $request->get('payload', $request->all());

        try {
            $result = $this->movementService->doneItem($payload);

            if (!$result['success']) {
                return $this->errorResponse([], $result['message']);
            }

            return $this->successfulResponse($result['data'], $result['message']);
        } catch (\Exception $ex) {
            return $this->errorResponse([], $ex->getMessage());
        }
    }

    /**
     * Remove order endpoint.
     */
    public function removeOrder(Request $request): JsonResponse
    {
        $payload = $request->get('payload', $request->all());

        try {
            $result = $this->movementService->removeOrder($payload);

            if (!$result['success']) {
                return $this->errorResponse([], $result['message']);
            }

            return $this->successfulResponse($result['data'], $result['message']);
        } catch (\Exception $ex) {
            return $this->errorResponse([], $ex->getMessage());
        }
    }

    /**
     * Remove menu endpoint.
     */
    public function removeMenu(Request $request): JsonResponse
    {
        $payload = $request->get('payload', $request->all());

        try {
            $result = $this->movementService->removeItem($payload);

            if (!$result['success']) {
                return $this->errorResponse([], $result['message']);
            }

            return $this->successfulResponse($result['data'], $result['message']);
        } catch (\Exception $ex) {
            return $this->errorResponse([], $ex->getMessage());
        }
    }

    /**
     * Get menu list.
     */
    public function getMenuList(Request $request): JsonResponse
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
     * Move menu to other station (legacy compatibility).
     */
    public function moveMenu(Request $request): JsonResponse
    {
        $payload = $request->get('payload', $request->all());

        try {
            $result = $this->movementService->moveItem($payload);

            if (!$result['success']) {
                return $this->errorResponse([], $result['message']);
            }

            return $this->successfulResponse($result['data'], $result['message']);
        } catch (\Exception $ex) {
            return $this->errorResponse([], $ex->getMessage());
        }
    }
}
