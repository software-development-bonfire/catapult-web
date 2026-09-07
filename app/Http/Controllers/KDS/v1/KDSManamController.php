<?php

namespace App\Http\Controllers\KDS\v1;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\KitchenDisplayRepository;
use App\Repositories\Eloquent\KitchenDisplayRepositoryEloquent;
use App\Services\KDS\KDSManamMovementService;
use App\Transformers\KDS\KitchenDisplay\AddonListTransformer;
use App\Transformers\KDS\KitchenDisplay\MenuListTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Fractalistic\ArraySerializer;

class KDSManamController extends Controller
{
    protected $movementService;

    public function __construct(KDSManamMovementService $movementService)
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

        Log::info('KDSManamController::action', ['action' => $action, 'payload' => $payload]);

        if (!$action) {
            return $this->errorResponse([], 'action is required');
        }

        if (empty($payload)) {
            return $this->errorResponse([], 'payload is required');
        }
        
        $result = $this->dispatchAction($action, $payload);

        if (!$result['success']) {
            return $this->errorResponse([], $result['message']);
        }

        return $this->successfulResponse($result['data'], $result['message']);
    }


    /**
     * Dispatch action to appropriate service method.
     */
    private function dispatchAction(string $action, array $payload): array
    {
        switch ($action) {
            case 'move_item':
                return $this->movementService->moveItem($payload);

            case 'move_order':
                return $this->movementService->moveOrder($payload);

            case 'bump_order':
                return $this->movementService->bumpOrder($payload);

            case 'undo_item':
                return $this->movementService->undoItem($payload);

            default:
                return [
                    'success' => false,
                    'message' => "Invalid action: $action",
                    'data' => null,
                ];
        }
    }
}
