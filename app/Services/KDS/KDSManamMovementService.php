<?php

namespace App\Services\KDS;

use App\Entities\CDISKitchenStation;
use App\Entities\DeviceSettings;
use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Entities\KitchenDisplayMovementHistory;
use App\Enums\API\DeviceType;
use App\Enums\KDS\KDSActionType;
use App\Enums\KDS\MenuStatus;
use App\Enums\KDS\QueueingGroup;
use App\Events\KDS\KDSFineDineTransactionEvent;
use App\Repositories\Contracts\KitchenItemSetupRepository;
use App\Traits\DatabaseTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Manam KDS Movement Service
 *
 * Handles movement operations for Manam fine-dining workflow where items move
 * through stages within a station (Prepare → Bump → Recall) as well as
 * traditional inter-station movement.
 *
 * action_type determines intra-station stage:
 *  1 = FOR_PREPARE
 *  2 = FOR_SERVE
 *  3 = FOR_BUMP
 *  4 = FOR_RECALL
 *  5 = FOR_DONE
 */
class KDSManamMovementService
{
    use DatabaseTransaction;

    /**
     * Process a move_item action.
     *
     * When action_type is present: performs intra-station stage movement
     *   - FOR_PREPARE(1): deduct remaining_qty from source, create/update FOR_BUMP target
     *   - FOR_BUMP(3): deduct remaining_qty & prepared_qty from source, create/update FOR_RECALL target
     *   - FOR_RECALL(4): deduct bumped_qty from source, return to FOR_BUMP target
     *
     * When action_type is absent: performs legacy inter-station movement
     *   (moves item to next/previous station)
     */
    public function moveItem(array $payload): array
    {
        $data = (object) ($payload['data'] ?? []);
        $items = $data->items ?? [];
        $transaction = (object) ($data->transaction ?? []);
        $orderType = (object) ($data->order_type ?? []);
        $next = $payload['next'] ?? true;
        $movedQuantity = (float) ($payload['moved_quantity'] ?? $payload['quantity'] ?? 0);
        $remainingQuantity = isset($payload['remaining_quantity']) ? (float) $payload['remaining_quantity'] : null;
        $originalQuantity = (float) ($payload['original_quantity'] ?? 0);
        $release = $payload['release'] ?? false;
        $actionType = isset($payload['action_type']) ? (int) $payload['action_type'] : null;
        $isReleasing = $payload['is_releasing'] ?? false;
        $recallReason = $payload['recall_reason'] ?? null;

        if (empty($items) || empty((array) $transaction)) {
            return $this->errorResult('Invalid payload: items and transaction are required');
        }

        $transactionId = $transaction->transaction_id ?? null;
        if (!$transactionId) {
            return $this->errorResult('transaction_id is required');
        }

        // For move_item, only one item in the array
        $item = (object) $items[0];

        Log::info('KDSManamMovementService::moveItem', [
            'transaction_id' => $transactionId,
            'action_type' => $actionType,
            'moved_quantity' => $movedQuantity,
            'remaining_quantity' => $remainingQuantity,
            'next' => $next,
            'release' => $release,
        ]);

        if ($actionType !== null) {
            // Intra-station stage movement
            return $this->handleStageMovement($item, $transaction, $orderType, $actionType, $movedQuantity, $remainingQuantity, $next, $isReleasing, $recallReason);
        }

        // Legacy inter-station movement
        return $this->handleInterStationMoveItem($item, $transaction, $orderType, $next, $movedQuantity, $remainingQuantity, $release);
    }

    /**
     * Process a undo_item action.
     *
     */
    public function undoItem(array $payload): array
    {

        log::info("UNDO Payload: ". json_encode($payload));
        log::info("End of payload");
        $data = (object) ($payload['data'] ?? []);
        $items = $data->items ?? [];
        $transaction = (object) ($data->transaction ?? []);
        $orderType = (object) ($data->order_type ?? []);
        $next = $payload['next'] ?? true;
        $movedQuantity = (float) ($payload['moved_quantity'] ?? $payload['quantity'] ?? 0);
        $remainingQuantity = isset($payload['remaining_quantity']) ? (float) $payload['remaining_quantity'] : null;
        $originalQuantity = (float) ($payload['original_quantity'] ?? 0);
        $release = $payload['release'] ?? false;
        $actionType = isset($payload['action_type']) ? (int) $payload['action_type'] : null;
        $isReleasing = $payload['is_releasing'] ?? false;
        $recallReason = $payload['recall_reason'] ?? null;
        
        if (empty($items) || empty((array) $transaction)) {
            return $this->errorResult('Invalid payload: items and transaction are required');
        }

        if (empty($items) || empty((array) $transaction)) {
            return $this->errorResult('Invalid payload: items and transaction are required');
        }

        $transactionId = $transaction->transaction_id ?? null;
        if (!$transactionId) {
            return $this->errorResult('transaction_id is required');
        }

        // For move_item, only one item in the array
        $item = (object) $items[0];

        Log::info('KDSManamMovementService::undoItem', [
            'transaction_id' => $transactionId,
            'action_type' => $actionType,
            'moved_quantity' => $movedQuantity,
            'remaining_quantity' => $remainingQuantity,
            'next' => $next,
            'release' => $release,
        ]);

        $transactionId = $item->transaction_id ?? $transaction->transaction_id ?? null;
        $terminalNumber = $item->terminal_number ?? $transaction->terminal_number ?? null;
        $productBid = $item->product_uom_packaging_bid ?? $item->product_bid ?? null;
        $addons = $item->addons ?? null;
        $kitchenStationBid = $item->kitchen_station_bid ?? null;
        $detailBid = $item->kitchen_display_detail_bid ?? null;

        // Resolve the source detail record — prefer exact match by bid + action_type
        $sourceDetail = null;
        if ($detailBid) {
            $sourceDetail = KitchenDisplayDetail::where('bid', $detailBid)
                ->where('action_type', $actionType)
                ->whereIn('status', [MenuStatus::ON_PROCESS, MenuStatus::WAITING])
                ->first();
        }

        log::info("detail BID:". $detailBid);
        log::info("source detail via detail bid:". json_encode($sourceDetail));
        // Fallback: resolve by action_type + identifiers
        if (!$sourceDetail) {
            $sourceDetail = $this->resolveDetailByActionType(
                $transactionId,
                $productBid,
                $terminalNumber,
                $kitchenStationBid,
                $actionType,
                $addons,
                $movedQuantity
            );
        }

        log::info("source detail via action type:". json_encode($sourceDetail));
        // BAR station fallback: if requesting FOR_SERVE but item is still at FOR_PREPARE,
        // auto-bump it first (BAR skips prepare/bump stages entirely)

        // if (!$sourceDetail && $actionType === KDSActionType::FOR_SERVE) {
        //     $sourceDetail = $this->resolveDetailByActionType(
        //         $transactionId, $productBid, $terminalNumber, $kitchenStationBid,
        //         KDSActionType::FOR_PREPARE, $addons
        //     );

        //     if ($sourceDetail) {
        //         // Auto-bump: convert FOR_PREPARE → FOR_SERVE in place
        //         $qty = (float) $sourceDetail->remaining_quantity;
        //         $sourceDetail->update([
        //             'action_type' => KDSActionType::FOR_SERVE,
        //             'bumped_quantity' => $qty,
        //             'remaining_quantity' => 0,
        //             'bumped_at' => now(),
        //         ]);
        //         $sourceDetail->refresh();
        //     }
        // }

        if (!$sourceDetail) {
            Log::warning('KDSManam: Source detail not found for stage movement', [
                'transaction_id' => $transactionId,
                'product_bid' => $productBid,
                'terminal_number' => $terminalNumber,
                'kitchen_station_bid' => $kitchenStationBid,
                'addons' => $addons,
                'action_type' => $actionType,
            ]);
            return $this->errorResult('Source item not found for stage movement');
        }

        return $this->undoStage($sourceDetail, $movedQuantity);
    }

    /**
     * Process a move_order action.
     * All items in the transaction move to the next station.
     */
    public function moveOrder(array $payload): array
    {
        $data = (object) ($payload['data'] ?? []);
        $items = $data->items ?? [];
        $transaction = (object) ($data->transaction ?? []);
        $orderType = (object) ($data->order_type ?? []);
        $next = $payload['next'] ?? true;
        $release = $payload['release'] ?? false;

        if (empty($items) || empty((array) $transaction)) {
            return $this->errorResult('Invalid payload: items and transaction are required');
        }

        $transactionId = $transaction->transaction_id ?? null;
        if (!$transactionId) {
            return $this->errorResult('transaction_id is required');
        }

        Log::info('KDSManamMovementService::moveOrder', [
            'transaction_id' => $transactionId,
            'items_count' => count($items),
            'next' => $next,
            'release' => $release,
        ]);

        $results = [];
        $released = false;
        $nextStationBid = null;

        foreach ($items as $itemData) {
            $item = (object) $itemData;
            $result = $this->processInterStationMovement($item, $transaction, $orderType, $next, $release);
            $results[] = $result;

            if ($result['released']) {
                $released = true;
            }
            if ($result['next_station_bid'] !== null) {
                $nextStationBid = $result['next_station_bid'];
            }
        }

        return [
            'success' => true,
            'message' => 'Order movement processed successfully',
            'data' => [
                'action' => 'move_order',
                'released' => $released,
                'next_station_bid' => $nextStationBid,
                'transaction_id' => $transactionId,
            ],
        ];
    }

    /**
     * Process a bump_order action.
     * All items in the transaction are updated to FOR_SERVE action_type.
     * This marks the entire order as ready for serving without deleting items.
     */
    public function bumpOrder(array $payload): array
    {
        $data = (object) ($payload['data'] ?? []);
        $items = $data->items ?? [];
        $transaction = (object) ($data->transaction ?? []);

        if (empty($items) || empty((array) $transaction)) {
            return $this->errorResult('Invalid payload: items and transaction are required');
        }

        $transactionId = $transaction->transaction_id ?? null;
        $terminalNumber = $transaction->terminal_number ?? null;

        if (!$transactionId) {
            return $this->errorResult('transaction_id is required');
        }

        Log::info('KDSManamMovementService::bumpOrder', [
            'transaction_id' => $transactionId,
            'items_count' => count($items),
        ]);

        return $this->transaction(function () use ($transactionId, $terminalNumber, $items) {
            // Update all active items for this transaction to FOR_SERVE
            $query = KitchenDisplayDetail::where('transaction_id', $transactionId)
                ->whereIn('status', [MenuStatus::ON_PROCESS, MenuStatus::WAITING]);

            if ($terminalNumber) {
                $query->where('terminal_number', $terminalNumber);
            }

            $details = $query->get();

            if ($details->isEmpty()) {
                return $this->errorResult('No active items found for this transaction');
            }

            foreach ($details as $detail) {
                $currentRemaining = (float) ($detail->remaining_quantity ?? 0);
                $currentBumped = (float) ($detail->bumped_quantity ?? 0);

                $detail->update([
                    'bumped_quantity' => $currentBumped + $currentRemaining,
                    'remaining_quantity' => 0,
                    'action_type' => KDSActionType::FOR_SERVE,
                    'bumped_at' => now(),
                ]);
            }

            // Broadcast full transaction state to releasing stations
            $firstDetail = $details->first();
            $this->broadcastStageMovement(
                $firstDetail,
                0,
                KDSActionType::FOR_BUMP,
                KDSActionType::FOR_SERVE,
                true
            );

            return [
                'success' => true,
                'message' => 'Order bumped to serve',
                'data' => [
                    'action' => 'bump_order',
                    'transaction_id' => $transactionId,
                ],
            ];
        });
    }

    // INTRA-STATION STAGE MOVEMENT

    /**
     * Handle intra-station stage movement based on action_type.
     *
     * Mirrors Flutter's _handleStageMovement logic:
     * - FOR_PREPARE → deduct remaining, create/update FOR_BUMP target
     * - FOR_BUMP → deduct remaining & prepared, create/update FOR_RECALL target
     * - FOR_RECALL → deduct bumped, return to FOR_BUMP target
     */
    private function handleStageMovement(
        object $item,
        object $transaction,
        object $orderType,
        int $actionType,
        float $movedQuantity,
        ?float $remainingQuantity,
        bool $next,
        bool $isReleasing = false,
        ?string $recallReason = null
    ): array {
        $transactionId = $item->transaction_id ?? $transaction->transaction_id ?? null;
        $terminalNumber = $item->terminal_number ?? $transaction->terminal_number ?? null;
        $productBid = $item->product_uom_packaging_bid ?? $item->product_bid ?? null;
        $addons = $item->addons ?? null;
        $kitchenStationBid = $item->kitchen_station_bid ?? null;
        $detailBid = $item->kitchen_display_detail_bid ?? null;

        // Resolve the source detail record — prefer exact match by bid + action_type
        $sourceDetail = null;
        if ($detailBid) {
            $sourceDetail = KitchenDisplayDetail::where('bid', $detailBid)
                ->where('action_type', $actionType)
                ->whereIn('status', [MenuStatus::ON_PROCESS, MenuStatus::WAITING])
                ->first();
        }

        // Fallback: resolve by action_type + identifiers
        if (!$sourceDetail) {
            $sourceDetail = $this->resolveDetailByActionType(
                $transactionId,
                $productBid,
                $terminalNumber,
                $kitchenStationBid,
                $actionType,
                $addons,
                $movedQuantity
            );
        }

        // BAR station fallback: if requesting FOR_SERVE but item is still at FOR_PREPARE,
        // auto-bump it first (BAR skips prepare/bump stages entirely)
        if (!$sourceDetail && $actionType === KDSActionType::FOR_SERVE) {
            $sourceDetail = $this->resolveDetailByActionType(
                $transactionId, $productBid, $terminalNumber, $kitchenStationBid,
                KDSActionType::FOR_PREPARE, $addons
            );

            if ($sourceDetail) {
                // Auto-bump: convert FOR_PREPARE → FOR_SERVE in place
                $qty = (float) $sourceDetail->remaining_quantity;
                $sourceDetail->update([
                    'action_type' => KDSActionType::FOR_SERVE,
                    'bumped_quantity' => $qty,
                    'remaining_quantity' => 0,
                    'bumped_at' => now(),
                ]);
                $sourceDetail->refresh();
            }
        }

        if (!$sourceDetail) {
            Log::warning('KDSManam: Source detail not found for stage movement', [
                'transaction_id' => $transactionId,
                'product_bid' => $productBid,
                'terminal_number' => $terminalNumber,
                'kitchen_station_bid' => $kitchenStationBid,
                'addons' => $addons,
                'action_type' => $actionType,
            ]);
            return $this->errorResult('Source item not found for stage movement');
        }

        return $this->transaction(function () use (
            $sourceDetail,
            $item,
            $transaction,
            $orderType,
            $actionType,
            $movedQuantity,
            $remainingQuantity,
            $next,
            $isReleasing,
            $recallReason
        ) {
            switch ($actionType) {
                case KDSActionType::FOR_PREPARE:
                    return $this->stageForPrepare($sourceDetail, $movedQuantity);

                case KDSActionType::FOR_BUMP:
                    return $this->stageForBump($sourceDetail, $movedQuantity);

                case KDSActionType::FOR_ASSEMBLY:
                    return $this->stageForAssemble($sourceDetail, $movedQuantity);

                case KDSActionType::FOR_RECALL:
                    return $this->stageForRecall($sourceDetail, $movedQuantity, $recallReason);

                case KDSActionType::FOR_SERVE:
                    return $this->stageForServe($sourceDetail, $movedQuantity, $isReleasing);

                default:
                    return $this->errorResult("Unsupported action_type: $actionType");
            }
        });
    }

    /**
     * FOR_PREPARE stage action:
     * Deducts remaining_quantity from source (FOR_PREPARE row).
     * Creates or updates a FOR_BUMP target row with prepared_quantity.
     */
    private function stageForPrepare(KitchenDisplayDetail $source, float $movedQty): array
    {
        $newRemaining = max(0, (float) $source->remaining_quantity - $movedQty);

        $source->update([
            'remaining_quantity' => $newRemaining,
        ]);

        // Create target row at FOR_BUMP stage
        $targetBid = $this->createOrUpdateTargetRow($source, $movedQty, KDSActionType::FOR_BUMP, [
            'remaining_quantity' => $movedQty,
            'prepared_quantity' => $movedQty,
        ]);

        // Record movement history
        $this->recordStageMovement($source->bid, KDSActionType::FOR_PREPARE, KDSActionType::FOR_BUMP, $movedQty);

        // Broadcast stage update to releasing stations
        $this->broadcastStageMovement($source, $movedQty, KDSActionType::FOR_PREPARE, KDSActionType::FOR_BUMP);
        
        // Broadcast stage update to non releasing device
        $this->broadcastToNonReleasingDevice($source);

        return [
            'success' => true,
            'message' => 'Item moved from Prepare to Bump',
            'data' => [
                'action' => 'move_item',
                'action_type' => KDSActionType::FOR_BUMP,
                'moved_quantity' => $movedQty,
                'target_bid' => $targetBid,
            ],
        ];
    }

    /**
     * FOR_ASSEMBLY stage action:
     * Deducts remaining_quantity and prepared_quantity from source (FOR_BUMP row).
     * Creates or updates a FOR_RECALL target row with bumped_quantity.
     */
    private function stageForBump(KitchenDisplayDetail $source, float $movedQty): array
    {
        $newRemaining = max(0, (float) $source->remaining_quantity - $movedQty);
        $newPrepared = max(0, (float) $source->prepared_quantity - $movedQty);

        $source->update([
            'remaining_quantity' => $newRemaining,
            'prepared_quantity' => $newPrepared,
        ]);

        // Create target row at FOR_ASSEMBLY stage
        $targetBid = $this->createOrUpdateTargetRow($source, $movedQty, KDSActionType::FOR_ASSEMBLY, [
            'bumped_quantity' => $movedQty,
        ]);

        // Record movement history
        $this->recordStageMovement($source->bid, KDSActionType::FOR_BUMP, KDSActionType::FOR_ASSEMBLY, $movedQty);

        // Broadcast stage update to releasing stations
        $this->broadcastStageMovement($source, $movedQty, KDSActionType::FOR_BUMP, KDSActionType::FOR_ASSEMBLY);
        
        // Broadcast stage update to non releasing device
        $this->broadcastToNonReleasingDevice($source);
        return [
            'success' => true,
            'message' => 'Item moved from Bump to Assemble',
            'data' => [
                'action' => 'move_item',
                'action_type' => KDSActionType::FOR_ASSEMBLY,
                'moved_quantity' => $movedQty,
                'target_bid' => $targetBid,
            ],
        ];
    }

    /**
     * FOR_BUMP stage action:
     * Deducts bumped_quantity  from source (FOR_ASSEMBLY row).
     * Creates or updates a FOR_RECALL target row with assembled_quantity.
     */
    private function stageForAssemble(KitchenDisplayDetail $source, float $movedQty): array
    {
        $newBumped = max(0, (float) $source->bumped_quantity - $movedQty);
        // $newRemaining = max(0, (float) $source->remaining_quantity - $movedQty);

        $source->update([
            // 'remaining_quantity' => $newRemaining,
            'bumped_quantity' => $newBumped,
        ]);
        

        // Create target row at FOR_SERVE stage
        $targetBid = $this->createOrUpdateTargetRow($source, $movedQty, KDSActionType::FOR_SERVE, [
            'assembled_quantity' => $movedQty,
        ]);

        // Record movement history
        $this->recordStageMovement($source->bid, KDSActionType::FOR_ASSEMBLY, KDSActionType::FOR_SERVE, $movedQty);

        // Broadcast stage update to releasing stations
        $this->broadcastStageMovement($source, $movedQty, KDSActionType::FOR_ASSEMBLY, KDSActionType::FOR_SERVE);
        
        // Broadcast stage update to non releasing device
        $this->broadcastToNonReleasingDevice($source);
        return [
            'success' => true,
            'message' => 'Item moved from Assemble to Serve',
            'data' => [
                'action' => 'move_item',
                'action_type' => KDSActionType::FOR_SERVE,
                'moved_quantity' => $movedQty,
                'target_bid' => $targetBid,
            ],
        ];
    }

    /**
     * FOR_RECALL stage action:
     * Deducts released_quantity from source (FOR_RECALL row).
     * Creates/updates FOR_SERVE target row with bumped_quantity and new bumped_at.
     * If all released_quantity is moved back, clears served_at on source.
     * Saves recall_reason on the source row.
     */
    private function stageForRecall(KitchenDisplayDetail $source, float $movedQty, ?string $recallReason = null): array
    {
        $newReleased = max(0, (float) $source->released_quantity - $movedQty);

        $updateData = [
            'released_quantity' => $newReleased,
        ];

        // Save recall reason on the source row
        if ($recallReason) {
            $updateData['recall_reason'] = $recallReason;
        }

        // If all released_quantity is recalled, clear served_at
        if ($newReleased <= 0) {
            $updateData['served_at'] = null;
        }

        $source->update($updateData);

        // Create or update target row back at FOR_SERVE stage with new bumped_at
        $targetBid = $this->updatePreviousAction($source, $movedQty, KDSActionType::FOR_SERVE);
        // $targetBid = $this->createOrUpdateTargetRow($source, $movedQty, KDSActionType::FOR_SERVE, [
        //     'assembled_quantity' => $movedQty,
        // ], ['assembled_at' => now ()]);

        // Record movement history
        $this->recordStageMovement($source->bid, KDSActionType::FOR_RECALL, KDSActionType::FOR_SERVE, $movedQty);

        // Broadcast stage update to releasing stations
        $this->broadcastStageMovement($source, $movedQty, KDSActionType::FOR_RECALL, KDSActionType::FOR_SERVE);
        
        // Broadcast stage update to non releasing device
        $this->broadcastToNonReleasingDevice($source);
        return [
            'success' => true,
            'message' => 'Item recalled back to Serve',
            'data' => [
                'action' => 'move_item',
                'action_type' => KDSActionType::FOR_SERVE,
                'moved_quantity' => $movedQty,
                'target_bid' => $targetBid,
            ],
        ];
    }

    /**
     * FOR_SERVE stage action (from releasing station):
     * Deducts bumped_quantity from source (FOR_SERVE row).
     * Creates or updates a FOR_RECALL target row with released_quantity.
     * Does NOT broadcast when originating from a releasing station.
     */
    private function stageForServe(KitchenDisplayDetail $source, float $movedQty, bool $isReleasing = false): array
    {
        $newAssembled = max(0, (float) $source->assembled_quantity - $movedQty);
        $newRemaining = max(0, (float) $source->remaining_quantity - $movedQty);

        $source->update([
            'assembled_quantity' => $newAssembled,
            'remaining_quantity' => $newRemaining,
        ]);

        // Create or update target row at FOR_RECALL stage
        $targetBid = $this->createOrUpdateTargetRow($source, $movedQty, KDSActionType::FOR_RECALL, [
            'released_quantity' => $movedQty,
        ]);

        // Record movement history
        $this->recordStageMovement($source->bid, KDSActionType::FOR_SERVE, KDSActionType::FOR_RECALL, $movedQty);

        // Skip broadcast if action originated from a releasing station
        //if (!$isReleasing) {
            $this->broadcastStageMovement($source, $movedQty, KDSActionType::FOR_SERVE, KDSActionType::FOR_RECALL);
        //}
        
        // Broadcast stage update to non releasing device
        $this->broadcastToNonReleasingDevice($source);
        return [
            'success' => true,
            'message' => 'Item moved from Serve to Recall',
            'data' => [
                'action' => 'move_item',
                'action_type' => KDSActionType::FOR_RECALL,
                'moved_quantity' => $movedQty,
                'target_bid' => $targetBid,
            ],
        ];
    }
    
    /**
     * UNDO stage action (from any station):
     * Deducts quantity from source (ANY row).
     * Updates a  target row with quantity.
     */
    private function undoStage(KitchenDisplayDetail $source, float $movedQty) : array
    {
        $kdsMovement = [
            0,
            KDSActionType::FOR_PREPARE,
            KDSActionType::FOR_BUMP,
            KDSActionType::FOR_ASSEMBLY,
            KDSActionType::FOR_SERVE,
            KDSActionType::FOR_RECALL
        ];
        $index = array_search($source->action_type, $kdsMovement);

        $kdsQuantity = [
            KDSActionType::FOR_RECALL => 'released_quantity',
            KDSActionType::FOR_SERVE => 'assembled_quantity',
            KDSActionType::FOR_ASSEMBLY => 'bumped_quantity',
            KDSActionType::FOR_BUMP => 'prepared_quantity',
        ];

        $kdsUndoQuantity = [
            KDSActionType::FOR_RECALL => 'assembled_quantity',
            KDSActionType::FOR_SERVE => 'bumped_quantity',
            KDSActionType::FOR_ASSEMBLY => 'prepared_quantity',
            KDSActionType::FOR_BUMP => 'remaining_quantity',
        ];

        $kdsUndoAction = [
            KDSActionType::FOR_RECALL => KDSActionType::FOR_SERVE,
            KDSActionType::FOR_SERVE => KDSActionType::FOR_ASSEMBLY,
            KDSActionType::FOR_ASSEMBLY => KDSActionType::FOR_BUMP,
            KDSActionType::FOR_BUMP => KDSActionType::FOR_PREPARE,
        ];

        $column = $kdsQuantity[$source->action_type];
        $value = $source->{$column};

        $newQuantity = max(0, (float) $value - $movedQty);

        $source->update([
            $kdsQuantity[$source->action_type] => $newQuantity,
        ]);

        log::info("Source: ". json_encode($source));
        log::info("Action Type Send By KDS: ". $source->action_type);
        log::info("Undo Value Action Type : ". $kdsUndoAction[$source->action_type]);
        // Update target action for UNDO
        $targetBid = $this->updatePreviousAction($source, $movedQty, $kdsUndoAction[$source->action_type]);
        log::info("BID of previous action: ". $source->action_type);
        $this->recordStageMovement($source->bid, $source->action_type, $kdsMovement[$index - 1], $movedQty);

        $this->broadcastStageMovement($source, $movedQty, $source->action_type, $kdsMovement[$index - 1]);
        
        // Broadcast stage update to non releasing device
        $this->broadcastToNonReleasingDevice($source);
        return [
            'success' => true,
            'message' => 'Item Undo movement',
            'data' => [
                'action' => 'undo_item',
                'action_type' => KDSActionType::UNDO,
                'moved_quantity' => $movedQty,
                'target_bid' => $targetBid,
            ],
        ];
    }

    private function updatePreviousAction(
        KitchenDisplayDetail $source,
        float $movedQty,
        int $targetActionType
    ) {
        $currentDateTime = now();

        $kdsQuantity = [
            KDSActionType::FOR_RECALL => 'released_quantity',
            KDSActionType::FOR_SERVE => 'assembled_quantity',
            KDSActionType::FOR_ASSEMBLY => 'bumped_quantity',
            KDSActionType::FOR_BUMP => 'prepared_quantity',
            KDSActionType::FOR_PREPARE => 'remaining_quantity',
        ];
        
        $targetCondition = [
            'head_bid' => $source->head_bid,
            'transaction_product_bid' => $source->transaction_product_bid,
            'product_uom_packaging_bid' => $source->product_uom_packaging_bid,
            'terminal_number' => $source->terminal_number,
            'kitchen_station_bid' => $source->kitchen_station_bid,
            'action_type' => $targetActionType,
        ];

        $existing = KitchenDisplayDetail::withTrashed()->where($targetCondition)->first();

        if ($existing) {
            $updateData = [];
            $value = $kdsQuantity[$targetActionType];
            $updateData['updated_at'] = $currentDateTime;
            $updateData[$value] = $existing->{$value} + $movedQty;
            if ($existing->trashed()) {
                $existing->restore();
            }

            $existing->update($updateData);
            return $existing->bid;
        }
    }

    /**
     * Creates a new target row or updates an existing one for the given action_type.
     *
     * FOR_RECALL target: merges into existing row (all recalled items accumulate).
     * All other targets (FOR_BUMP, FOR_SERVE): always creates a new row so each
     * batch retains its own elapsed time from the moment it was moved.
     */
    private function createOrUpdateTargetRow(
        KitchenDisplayDetail $source,
        float $movedQty,
        int $targetActionType,
        array $incrementFields,
        array $extraUpdates = []
    ): ?string {
        $currentDateTime = now();

        // FOR_RECALL: merge into existing row (accumulate recalled items)
        if ($targetActionType === KDSActionType::FOR_RECALL) {
            $targetCondition = [
                'head_bid' => $source->head_bid,
                'transaction_product_bid' => $source->transaction_product_bid,
                'product_uom_packaging_bid' => $source->product_uom_packaging_bid,
                'terminal_number' => $source->terminal_number,
                'kitchen_station_bid' => $source->kitchen_station_bid,
                'action_type' => $targetActionType,
            ];

            if ($source->addons) {
                $targetCondition['addons'] = $source->addons;
            }

            $existing = KitchenDisplayDetail::withTrashed()->where($targetCondition)->first();

            if ($existing) {
                $updateData = [];
                foreach ($incrementFields as $field => $value) {
                    $currentValue = (float) ($existing->{$field} ?? 0);
                    $updateData[$field] = $currentValue + $value;
                }
                $updateData['updated_at'] = $currentDateTime;

                foreach ($extraUpdates as $key => $value) {
                    $updateData[$key] = $value;
                }

                if ($existing->trashed()) {
                    $existing->restore();
                }

                $existing->update($updateData);
                return $existing->bid;
            }
        }
        

        // FOR_BUMP, FOR_SERVE, or new FOR_RECALL: always create a new row
        // Each batch has its own timing for elapsed time display
        $newData = [
            'head_bid' => $source->head_bid,
            'transaction_product_bid' => $source->transaction_product_bid,
            'product_uom_packaging_bid' => $source->product_uom_packaging_bid,
            'transaction_id' => $source->transaction_id,
            'transaction_type' => $source->transaction_type,
            'kitchen_station_bid' => $source->kitchen_station_bid,
            'status' => $source->status,
            'action_type' => $targetActionType,
            'order_type_id' => $source->order_type_id,
            'order_type_name' => $source->order_type_name,
            'usage_type' => $source->usage_type,
            'special_request' => $source->special_request,
            'addons' => $source->addons,
            'is_addon' => $source->is_addon,
            'name' => $source->name,
            'terminal_number' => $source->terminal_number,
            'max_preparation_time' => $source->max_preparation_time,
            'sent_at' => $currentDateTime,
            'started_at' => $source->started_at,
            // Quantity fields from increment
            'remaining_quantity' => $incrementFields['remaining_quantity'] ?? 0,
            'prepared_quantity' => $incrementFields['prepared_quantity'] ?? 0,
            'bumped_quantity' => $incrementFields['bumped_quantity'] ?? 0,
            'assembled_quantity' => $incrementFields['assembled_quantity'] ?? 0,
            'released_quantity' => $incrementFields['released_quantity'] ?? 0,
        ];


        
        // Set stage timestamps
        if ($targetActionType === KDSActionType::FOR_BUMP) {
            $newData['prepared_at'] = $currentDateTime;
        } elseif ($targetActionType === KDSActionType::FOR_ASSEMBLY) {
            $newData['bumped_at'] = $currentDateTime;
        } elseif ($targetActionType === KDSActionType::FOR_SERVE) {
            $newData['assembled_at'] = $currentDateTime;
        } elseif ($targetActionType === KDSActionType::FOR_RECALL) {
            $newData['served_at'] = $currentDateTime;
        }

        // Apply extra updates
        foreach ($extraUpdates as $key => $value) {
            $newData[$key] = $value;
        }

        $newRow = KitchenDisplayDetail::create($newData);
        return $newRow->bid ?? null;
    }

    // INTER-STATION MOVEMENT, dati from legacy move_item logic, now refactored to support both move_item and move_order

    /**
     * Handle inter-station movement for a single item (legacy move without action_type).
     */
    private function handleInterStationMoveItem(
        object $item,
        object $transaction,
        object $orderType,
        bool $next,
        float $movedQuantity,
        ?float $remainingQuantity,
        bool $release
    ): array {
        $result = $this->processInterStationMovement($item, $transaction, $orderType, $next, $release, $movedQuantity, $remainingQuantity);

        $transactionId = $item->transaction_id ?? $transaction->transaction_id ?? null;

        return [
            'success' => true,
            'message' => 'Item movement processed successfully',
            'data' => [
                'action' => 'move_item',
                'released' => $result['released'],
                'next_station_bid' => $result['next_station_bid'],
                'transaction_id' => $transactionId,
            ],
        ];
    }

    /**
     * Process inter-station movement for a single item.
     * Resolves the next station and moves the item forward/backward.
     */
    private function processInterStationMovement(
        object $item,
        object $transaction,
        object $orderType,
        bool $next,
        bool $forceRelease,
        float $movedQuantity = 0,
        ?float $remainingQuantity = null
    ): array {
        $transactionId = $item->transaction_id ?? $transaction->transaction_id ?? null;
        $transactionProductBid = $item->transaction_product_bid ?? null;
        $productUomPackagingBid = $item->product_uom_packaging_bid ?? $item->product_bid ?? null;
        $terminalNumber = $item->terminal_number ?? $transaction->terminal_number ?? null;
        $currentStationBid = $item->kitchen_station_bid ?? null;

        // Find the KitchenDisplayDetail record
        $detail = $this->resolveDetail($transactionId, $transactionProductBid, $productUomPackagingBid, $terminalNumber, $currentStationBid);

        if (!$detail) {
            Log::warning('KDSManam: Detail not found for inter-station movement', [
                'transaction_id' => $transactionId,
                'product_bid' => $productUomPackagingBid,
                'station_bid' => $currentStationBid,
            ]);
            return ['released' => false, 'next_station_bid' => null];
        }

        // If moved quantity is 0, use the full remaining quantity
        if ($movedQuantity <= 0) {
            $movedQuantity = (float) ($detail->remaining_quantity ?? 0);
        }

        // Determine next station
        $nextStationBid = $this->determineNextStation($detail, $next);

        $released = false;
        if ($nextStationBid === null || $forceRelease) {
            $nextStationBid = null;
            $released = true;
        }

        $isPartialMove = $remainingQuantity !== null && $remainingQuantity > 0 && $movedQuantity < (float) $detail->remaining_quantity;

        if (!$isPartialMove) {
            $movedQuantity = (float) ($detail->remaining_quantity ?? $movedQuantity);
        }

        return $this->transaction(function () use (
            $detail,
            $currentStationBid,
            $nextStationBid,
            $movedQuantity,
            $remainingQuantity,
            $isPartialMove,
            $released,
            $item,
            $transaction,
            $orderType,
            $next
        ) {
            if ($isPartialMove) {
                $this->handlePartialMove($detail, $nextStationBid, $movedQuantity, $remainingQuantity - $movedQuantity, $released);
            } else {
                $this->handleFullMove($detail, $nextStationBid, $movedQuantity, $released);
            }

            // Record movement history
            $this->recordMovement(
                $detail->bid,
                $detail->kitchen_station_bid,
                $nextStationBid,
                $movedQuantity,
                $released ? 'TO_RELEASING' : ($next ? 'FORWARD' : 'BACKWARD')
            );

            // Update completed_quantity on head
            $this->updateHeadCompletedQuantity($detail->head_bid);

            // Broadcast events
            $this->broadcastMovement($detail, $currentStationBid, $nextStationBid, $movedQuantity, $released, $item, $transaction, $orderType, $next);

            return ['released' => $released, 'next_station_bid' => $nextStationBid];
        });
    }

    // SHARED MOVEMENT HELPERS

    /**
     * Handle partial quantity move — reduces current detail qty, merges or creates at next station.
     */
    private function handlePartialMove(
        KitchenDisplayDetail $detail,
        ?string $toStationBid,
        float $movedQuantity,
        float $newRemainingQuantity,
        bool $released
    ): void {
        $detail->update([
            'remaining_quantity' => $newRemainingQuantity,
        ]);

        $this->mergeOrCreateAtStation($detail, $toStationBid, $movedQuantity, $released);
    }

    /**
     * Handle full item move — entire item moves to next station.
     */
    private function handleFullMove(
        KitchenDisplayDetail $detail,
        ?string $toStationBid,
        float $movedQuantity,
        bool $released
    ): void {
        $existingAtDestination = $this->findExistingAtStation($detail, $toStationBid);

        if ($existingAtDestination) {
            $newQuantity = (float) $existingAtDestination->remaining_quantity + $movedQuantity;
            $existingAtDestination->update([
                'remaining_quantity' => $newQuantity,
            ]);

            $detail->update([
                'remaining_quantity' => 0,
                'status' => MenuStatus::DONE,
                'end_at' => now(),
            ]);
            $detail->delete();
        } else {
            $newStatus = $released ? MenuStatus::RELEASING : MenuStatus::ON_PROCESS;

            $detail->update([
                'kitchen_station_bid' => $toStationBid,
                'status' => $newStatus,
                'started_at' => now(),
                'end_at' => null,
            ]);
        }
    }

    /**
     * Merge quantity into existing record at station, or create new record.
     */
    private function mergeOrCreateAtStation(
        KitchenDisplayDetail $sourceDetail,
        ?string $targetStationBid,
        float $quantity,
        bool $released
    ): void {
        $existing = $this->findExistingAtStation($sourceDetail, $targetStationBid);

        if ($existing) {
            $newQuantity = (float) $existing->remaining_quantity + $quantity;
            $existing->update(['remaining_quantity' => $newQuantity]);
        } else {
            $newStatus = $released ? MenuStatus::RELEASING : MenuStatus::ON_PROCESS;

            KitchenDisplayDetail::create([
                'head_bid' => $sourceDetail->head_bid,
                'transaction_product_bid' => $sourceDetail->transaction_product_bid,
                'product_uom_packaging_bid' => $sourceDetail->product_uom_packaging_bid,
                'transaction_id' => $sourceDetail->transaction_id,
                'remaining_quantity' => $quantity,
                'prepared_quantity' => 0,
                'bumped_quantity' => 0,
                'released_quantity' => 0,
                'kitchen_station_bid' => $targetStationBid,
                'status' => $newStatus,
                'action_type' => KDSActionType::FOR_PREPARE,
                'order_type_id' => $sourceDetail->order_type_id,
                'order_type_name' => $sourceDetail->order_type_name,
                'usage_type' => $sourceDetail->usage_type,
                'special_request' => $sourceDetail->special_request,
                'addons' => $sourceDetail->addons,
                'is_addon' => $sourceDetail->is_addon,
                'name' => $sourceDetail->name,
                'terminal_number' => $sourceDetail->terminal_number,
                'started_at' => now(),
            ]);
        }
    }

    // RESOLUTION & QUERY HELPERS

    /**
     * Find existing detail record at a target station for the same transaction item.
     */
    private function findExistingAtStation(KitchenDisplayDetail $detail, ?string $stationBid): ?KitchenDisplayDetail
    {
        return KitchenDisplayDetail::where('head_bid', $detail->head_bid)
            ->where('transaction_product_bid', $detail->transaction_product_bid)
            ->where('product_uom_packaging_bid', $detail->product_uom_packaging_bid)
            ->where('kitchen_station_bid', $stationBid)
            ->where('terminal_number', $detail->terminal_number)
            ->first();
    }

    /**
     * Resolve KitchenDisplayDetail by action_type for stage movement.
     * Only returns rows that still have relevant quantity to move (skips depleted rows).
     */
    private function resolveDetailByActionType(
        ?string $transactionId,
        ?string $productBid,
        ?string $terminalNumber,
        ?string $kitchenStationBid,
        int $actionType,
        ?string $addons = null,
        ?float $movedQuantity = null
    ): ?KitchenDisplayDetail {
        $query = KitchenDisplayDetail::whereIn('status', [MenuStatus::ON_PROCESS, MenuStatus::WAITING])
            ->where('action_type', $actionType);

        // Only resolve rows with non-zero relevant quantity for the action type
        if ($actionType === KDSActionType::FOR_PREPARE) {
            $query->where('remaining_quantity', '>', 0);
        } else if ($actionType === KDSActionType::FOR_BUMP) {
             $query->where('prepared_quantity', '>', 0);
        } elseif ($actionType === KDSActionType::FOR_ASSEMBLY) {
            $query->where('bumped_quantity', '>', 0);
        } elseif ($actionType === KDSActionType::FOR_SERVE) {
            $query->where('assembled_quantity', '>', 0);
        } elseif ($actionType === KDSActionType::FOR_RECALL) {
            $query->where('released_quantity', '>', 0);
        }

        if ($transactionId) {
            $query->where('transaction_id', $transactionId);
        }

        if ($productBid) {
            $query->where('product_uom_packaging_bid', $productBid);
        }

        if ($terminalNumber) {
            $query->where('terminal_number', $terminalNumber);
        }

        if ($kitchenStationBid) {
            $query->where('kitchen_station_bid', $kitchenStationBid);
        }

        if ($addons !== null) {
            $query->where('addons', $addons);
        }
        
        // Prefer row with exact relevant-quantity match (likely the row the client is acting on),
        // then fall back to FIFO ordering for deterministic picking
        if ($movedQuantity !== null && $movedQuantity > 0) {
            $quantityField = 'remaining_quantity';
            if ($actionType === KDSActionType::FOR_SERVE) {
                $quantityField = 'assembled_quantity';
            } elseif ($actionType === KDSActionType::FOR_RECALL) {
                $quantityField = 'released_quantity';
            }
            $exactMatch = (clone $query)->where($quantityField, $movedQuantity)->orderBy('created_at', 'asc')->first();
            if ($exactMatch) {
                return $exactMatch;
            }
        }

        return $query->orderBy('created_at', 'asc')->first();
    }

    /**
     * Resolve KitchenDisplayDetail from item identifiers (for inter-station movement).
     */
    private function resolveDetail(
        ?string $transactionId,
        ?string $transactionProductBid,
        ?string $productUomPackagingBid,
        ?string $terminalNumber,
        ?string $kitchenStationBid
    ): ?KitchenDisplayDetail {
        $query = KitchenDisplayDetail::whereIn('status', [MenuStatus::ON_PROCESS, MenuStatus::WAITING]);

        if ($transactionId) {
            $query->where('transaction_id', $transactionId);
        }

        if ($transactionProductBid) {
            $query->where('transaction_product_bid', $transactionProductBid);
        }

        if ($productUomPackagingBid) {
            $query->where('product_uom_packaging_bid', $productUomPackagingBid);
        }

        if ($terminalNumber) {
            $query->where('terminal_number', $terminalNumber);
        }

        if ($kitchenStationBid) {
            $query->where('kitchen_station_bid', $kitchenStationBid);
        }

        return $query->first();
    }

    /**
     * Determine the next station using KitchenItemSetupRepository.
     */
    private function determineNextStation(KitchenDisplayDetail $detail, bool $next): ?string
    {
        $productBid = $detail->product_uom_packaging_bid;
        $currentStationBid = $detail->kitchen_station_bid;

        $stations = [];
        for ($index = 1; $index <= 4; $index++) {
            $setup = app()->make(KitchenItemSetupRepository::class)
                ->getKitchenStation($productBid, $index);

            if ($setup && !empty($setup['station_bid_' . $index])) {
                $stations[] = $setup['station_bid_' . $index];
            }
        }

        if (empty($stations)) {
            return null;
        }

        $currentPos = array_search($currentStationBid, $stations);

        if ($currentPos === false) {
            return null;
        }

        $targetPos = $next ? $currentPos + 1 : $currentPos - 1;

        if ($targetPos < 0 || $targetPos >= count($stations)) {
            return null;
        }

        return $stations[$targetPos];
    }

    // HISTORY & BROADCASTING

    /**
     * Broadcast stage movement to all releasing station devices.
     *
     * Sends the moved item with target action_type so releasing stations
     * can create/update a matching row to mirror the preparation progress.
     *
     * The releasing station keeps the original P rows unchanged and adds
     * new rows for B/R stages as items move through preparation.
     */
    private function broadcastStageMovement(
        KitchenDisplayDetail $source,
        float $movedQty,
        int $fromActionType,
        int $toActionType,
        bool $fullTransaction = true
    ): void {
        $releasingDeviceUids = $this->getReleasingStationDeviceUids();

        if (empty($releasingDeviceUids)) {
            return;
        }

        // Build transaction data from the head record
        $head = KitchenDisplay::where('bid', $source->head_bid)->first();
        $transactionData = [
            'transaction_id' => $source->transaction_id,
            'terminal_number' => $source->terminal_number,
        ];
        if ($head) {
            $transactionData['terminal_bid'] = $head->terminal_bid ?? null;
            $transactionData['transaction_date'] = $head->transaction_date ?? null;
        }

        if ($fullTransaction) {
            $barBidStation = [
                1000000000000000040
            ];
            // Full transaction sync: send ALL non-depleted items for the transaction so
            // releasing station can delete-insert for an accurate mirror.
            $allDetails = KitchenDisplayDetail::where('transaction_id', $source->transaction_id)
                ->where('terminal_number', $source->terminal_number)
                ->whereIn('status', [MenuStatus::ON_PROCESS, MenuStatus::WAITING])
                ->where(function ($q) {
                    // Exclude depleted rows (all quantities are 0)
                    $q->where('remaining_quantity', '>', 0)
                      ->orWhere('prepared_quantity', '>', 0)
                      ->orWhere('assembled_quantity', '>', 0)
                      ->orWhere('bumped_quantity', '>', 0)
                      ->orWhere('released_quantity', '>', 0);
                })
                //remove bar items in releasing station
                ->whereNotIn('kitchen_station_bid', $barBidStation)
                ->get();

            $allItemsPayload = [];
            foreach ($allDetails as $detail) {               
                $allItemsPayload[] = $this->buildItemPayload($detail);
            }

            foreach ($releasingDeviceUids as $deviceUid) {
                broadcast(new KDSFineDineTransactionEvent(
                    $deviceUid,
                    (object) $transactionData,
                    $allItemsPayload,
                    true,
                    'STAGE_UPDATE_FULL'
                ));
            }
            
        } else {
            // Incremental: send only the moved item with target action_type
            $itemData = $this->buildItemPayload($source, [
                'remaining_quantity' => $movedQty,
                'moved_quantity' => $movedQty,
                'action_type' => $toActionType,
                'from_action_type' => $fromActionType,
                'prepared_quantity' => 0,
                'bumped_quantity' => 0,
                'assembled_quantity' => 0,
                'released_quantity' => 0,
            ]);

            // Set the quantity fields matching the target action_type
            if ($toActionType === KDSActionType::FOR_BUMP) {
                $itemData['prepared_quantity'] = $movedQty;
            } elseif ($toActionType === KDSActionType::FOR_ASSEMBLY) {
                $itemData['bumped_quantity'] = $movedQty;
            } elseif ($toActionType === KDSActionType::FOR_SERVE) {
                $itemData['assembled_quantity'] = $movedQty;
            } elseif ($toActionType === KDSActionType::FOR_RECALL) {
                $itemData['released_quantity'] = $movedQty;
            }

            foreach ($releasingDeviceUids as $deviceUid) {
                broadcast(new KDSFineDineTransactionEvent(
                    $deviceUid,
                    (object) $transactionData,
                    [$itemData],
                    true,
                    'STAGE_UPDATE'
                ));
            }
        }
    }

    private function broadcastToNonReleasingDevice(KitchenDisplayDetail $source)
    {

        log::info("Broadcasting Source: ". json_encode($source));
        // Build transaction data from the head record
        $head = KitchenDisplay::where('bid', $source->head_bid)->first();
        $transactionData = [
            'transaction_id' => $source->transaction_id,
            'terminal_number' => $source->terminal_number,
        ];
        if ($head) {
            $transactionData['terminal_bid'] = $head->terminal_bid ?? null;
            $transactionData['transaction_date'] = $head->transaction_date ?? null;
        }

        $barBidStation = [
            1000000000000000040
        ];
        // Full transaction sync: send 1st station non-depleted items for the transaction so
        // non releasing station can delete-insert for an accurate mirror.
        $allDetails = KitchenDisplayDetail::where('transaction_id', $source->transaction_id)
            ->where('terminal_number', $source->terminal_number)
            ->whereIn('status', [MenuStatus::ON_PROCESS, MenuStatus::WAITING])
            ->where(function ($q) {
                // Exclude depleted rows (all quantities are 0)
                $q->where('remaining_quantity', '>', 0)
                    ->orWhere('prepared_quantity', '>', 0);
            })
            //remove bar items in releasing station
            // ->whereNotIn('kitchen_station_bid', $barBidStation)
            ->get();
        $allItemsPayload = [];
        foreach ($allDetails as $detail) {               
            $allItemsPayload[] = $this->buildItemPayload($detail);
        }

        $allDeviceUids = collect($allItemsPayload)
                        ->pluck('device_uid')
                        ->filter()
                        ->unique()
                        ->values();
         log::info("Broadcasting Payload: ". json_encode($allItemsPayload));
         log::info("Broadcasting DeviceUIDs: ". json_encode($allDeviceUids));
        foreach ($allDeviceUids as $device) {
            log::info("broadcast to device:". $device);
            broadcast(new KDSFineDineTransactionEvent(
                $device,
                (object) $transactionData,
                $allItemsPayload,
                false,
                'STAGE_UPDATE_FULL'
            ));
        }

        log::info("done broadcasting");
    }

    /**
     * Record stage movement in history table.
     */
    private function recordStageMovement(string $detailBid, int $fromActionType, int $toActionType, float $quantity): void
    {
        // Ano muna to testing lang, pero dapat palitan na ng station index kapag available na
        KitchenDisplayMovementHistory::create([
            'detail_bid' => $detailBid,
            'from_station_index' => $fromActionType, // Replace this with station index
            'to_station_index' => $toActionType, // Replace this with station index
            'quantity_moved' => $quantity,
            'movement_type' => 'STAGE',
            'status_before' => MenuStatus::ON_PROCESS,
            'status_after' => MenuStatus::ON_PROCESS,
        ]);
    }

    /**
     * Build a standardized item payload matching Flutter Item.fromMap properties.
     * Used for broadcasting stage movements to releasing stations.
     */
    private function buildItemPayload(KitchenDisplayDetail $detail, array $overrides = []): array
    {
        // Resolve station code and name from kitchen_station_bid
        $stationCode = null;
        $stationName = null;
        $deviceUID = null;
        if ($detail->kitchen_station_bid) {
            $station = CDISKitchenStation::where('bid', $detail->kitchen_station_bid)->first();
            if ($station) {
                $stationCode = $station->code;
                $stationName = $station->name;
            }
            $deviceUID = $this->getDeviceUidForStation($detail->kitchen_station_bid);
        }

        $payload = [
            'bid' => $detail->product_uom_packaging_bid,
            'head_bid' => $detail->head_bid,
            'product_bid' => $detail->product_uom_packaging_bid,
            'transaction_product_bid' => $detail->transaction_product_bid,
            'product_uom_packaging_bid' => $detail->product_uom_packaging_bid,
            'transaction_id' => $detail->transaction_id,
            'transaction_type' => $detail->transaction_type,
            'name' => $detail->name,
            'quantity' => $detail->remaining_quantity,
            'remaining_quantity' => $detail->remaining_quantity,
            'prepared_quantity' => $detail->prepared_quantity ?? 0,
            'bumped_quantity' => $detail->bumped_quantity ?? 0,
            'assembled_quantity' => $detail->assembled_quantity ?? 0,
            'released_quantity' => $detail->released_quantity ?? 0,
            'usage_type' => $detail->usage_type,
            'special_request' => $detail->special_request,
            'is_addon' => $detail->is_addon,
            'is_additional' => $detail->is_additional ?? 0,
            'is_removed' => $detail->is_removed ?? 0,
            'kitchen_station_process_bid' => null,
            'kitchen_station_index' => 1,
            'kitchen_display_bid' => $detail->head_bid,
            'kitchen_display_detail_bid' => $detail->bid,
            'kitchen_transaction_detail_bid' => null,
            'station_code' => $stationCode,
            'station_name' => $stationName,
            'device_code' => null,
            'device_uid' => $deviceUID,
            'device_name' => null,
            'order_type' => $detail->order_type_id,
            'order_type_id' => $detail->order_type_id ?? '',
            'order_type_name' => $detail->order_type_name,
            'terminal_number' => $detail->terminal_number,
            'table_number' => null,
            'queue_number' => null,
            'addons' => $detail->addons,
            'max_prep_time' => $detail->max_preparation_time ?? 0,
            'max_waiting_time' => $detail->max_waiting_time ?? 0,
            'max_serving_time' => $detail->max_serving_time ?? 0,
            'max_assembling_time' => $detail->max_assembly_time ?? 1,
            'pos_description' => null,
            'short_description' => null,
            'long_description' => null,
            'menu_description' => null,
            'item_type' => 1,
            'action_type' => $detail->action_type ?? KDSActionType::FOR_PREPARE,
            'presentation_url' => null,
            'recipe_url' => null,
            'kitchen_station_bid' => $detail->kitchen_station_bid,
            'status' => $detail->status,
            'created_at' => $detail->created_at ? $detail->created_at->utc()->format('Y-m-d\TH:i:s\Z') : null,
            'updated_at' => $detail->updated_at ? $detail->updated_at->utc()->format('Y-m-d\TH:i:s\Z') : null,
            'sent_at' => $detail->sent_at ? (is_string($detail->sent_at) ? Carbon::parse($detail->sent_at)->utc()->format('Y-m-d\TH:i:s\Z') : $detail->sent_at->utc()->format('Y-m-d\TH:i:s\Z')) : null,
            'prepared_at' => $detail->prepared_at ? (is_string($detail->prepared_at) ? Carbon::parse($detail->prepared_at)->utc()->format('Y-m-d\TH:i:s\Z') : $detail->prepared_at->utc()->format('Y-m-d\TH:i:s\Z')) : null,
            'bumped_at' => $detail->bumped_at ? (is_string($detail->bumped_at) ? Carbon::parse($detail->bumped_at)->utc()->format('Y-m-d\TH:i:s\Z') : $detail->bumped_at->utc()->format('Y-m-d\TH:i:s\Z')) : null,
            'assembled_at' => $detail->assembled_at ? (is_string($detail->assembled_at) ? Carbon::parse($detail->assembled_at)->utc()->format('Y-m-d\TH:i:s\Z') : $detail->assembled_at->utc()->format('Y-m-d\TH:i:s\Z')) : null,
            'served_at' => $detail->served_at ? (is_string($detail->served_at) ? Carbon::parse($detail->served_at)->utc()->format('Y-m-d\TH:i:s\Z') : $detail->served_at->utc()->format('Y-m-d\TH:i:s\Z')) : null,
            'recall_reason' => $detail->recall_reason,
            'deleted_at' => $detail->deleted_at ? $detail->deleted_at->utc()->format('Y-m-d\TH:i:s\Z') : null,
        ];

        return array_merge($payload, $overrides);
    }

    /**
     * Record inter-station movement in history table.
     */
    private function recordMovement(
        string $detailBid,
        ?string $fromStationBid,
        ?string $toStationBid,
        float $quantity,
        string $movementType
    ): void {
        $fromIndex = $this->resolveStationIndex($fromStationBid);
        $toIndex = $toStationBid ? $this->resolveStationIndex($toStationBid) : 0;

        KitchenDisplayMovementHistory::create([
            'detail_bid' => $detailBid,
            'from_station_index' => $fromIndex,
            'to_station_index' => $toIndex,
            'quantity_moved' => $quantity,
            'movement_type' => $movementType,
            'status_before' => MenuStatus::ON_PROCESS,
            'status_after' => MenuStatus::ON_PROCESS,
        ]);
    }

    /**
     * Resolve a station_bid to its index number.
     */
    private function resolveStationIndex(?string $stationBid): int
    {
        if (!$stationBid) {
            return 0;
        }

        $station = CDISKitchenStation::where('bid', $stationBid)->first();
        return $station ? (int) ($station->station_index ?? $station->index ?? 0) : 0;
    }

    /**
     * Update the completed_quantity on the head record.
     */
    private function updateHeadCompletedQuantity(string $headBid): void
    {
        $head = KitchenDisplay::where('bid', $headBid)->first();
        if (!$head) {
            return;
        }

        $completedQty = KitchenDisplayDetail::withTrashed()
            ->where('head_bid', $headBid)
            ->where('status', MenuStatus::DONE)
            ->sum('remaining_quantity');

        $releasingQty = KitchenDisplayDetail::withTrashed()
            ->where('head_bid', $headBid)
            ->where('status', MenuStatus::RELEASING)
            ->sum('remaining_quantity');

        $head->update([
            'completed_quantity' => $completedQty + $releasingQty,
        ]);

        $activeCount = KitchenDisplayDetail::where('head_bid', $headBid)
            ->where('status', MenuStatus::ON_PROCESS)
            ->count();

        if ($activeCount === 0 && !$head->completed_at) {
            $head->update(['completed_at' => now()]);
        }
    }

    /**
     * Broadcast movement events to affected KDS devices.
     */
    private function broadcastMovement(
        KitchenDisplayDetail $detail,
        ?string $fromStationBid,
        ?string $toStationBid,
        float $movedQuantity,
        bool $released,
        object $item,
        object $transaction,
        object $orderType,
        bool $next
    ): void {
        $transactionData = (array) $transaction;
        $transactionData['kitchen_station_bid'] = $toStationBid;

        $itemData = [
            'bid' => $detail->product_uom_packaging_bid,
            'product_bid' => $detail->product_uom_packaging_bid,
            'transaction_product_bid' => $detail->transaction_product_bid,
            'name' => $detail->name,
            'quantity' => $detail->quantity,
            'remaining_quantity' => $detail->remaining_quantity,
            'moved_quantity' => $movedQuantity,
            'prepared_quantity' => $detail->prepared_quantity,
            'bumped_quantity' => $detail->bumped_quantity,
            'assembled_quantity' => $detail->assembled_quantity,
            'released_quantity' => $detail->released_quantity,
            'action_type' => $detail->action_type,
            'usage_type' => $detail->usage_type,
            'special_request' => $detail->special_request,
            'is_addon' => $detail->is_addon,
            'is_additional' => $detail->is_additional,
            'is_removed' => $detail->is_removed,
            'transaction_id' => $detail->transaction_id,
            'order_type_name' => $detail->order_type_name,
            'order_type_id' => $detail->order_type_id ?? '',
            'terminal_number' => $detail->terminal_number,
            'addons' => $detail->addons,
            'kitchen_station_bid' => $toStationBid,
            'status' => $detail->status,
        ];

        if ($released) {
            if (!$next) {
                // Send-back from releasing → notify non-releasing stations
                $nonReleasingDeviceUids = $this->getNonReleasingStationDeviceUids();
                foreach ($nonReleasingDeviceUids as $deviceUid) {
                    broadcast(new KDSFineDineTransactionEvent(
                        $deviceUid,
                        (object) $transactionData,
                        [$itemData],
                        false,
                        'RECALL'
                    ));
                }
            } else {
                // Forward to releasing station
                $originalQuantity = (float) ($item->quantity ?? $movedQuantity);
                $releasingItemData = array_merge($itemData, [
                    'quantity' => $originalQuantity,
                    'remaining_quantity' => $movedQuantity,
                ]);

                $releasingDeviceUids = $this->getReleasingStationDeviceUids();
                foreach ($releasingDeviceUids as $deviceUid) {
                    broadcast(new KDSFineDineTransactionEvent(
                        $deviceUid,
                        (object) $transactionData,
                        [$releasingItemData],
                        true
                    ));
                }
            }
        } else {
            // Broadcast to target station device
            $deviceUid = $this->getDeviceUidForStation($toStationBid);
            if ($deviceUid) {
                broadcast(new KDSFineDineTransactionEvent(
                    $deviceUid,
                    (object) $transactionData,
                    [$itemData],
                    false
                ));
            }

            // Also notify releasing station
            $originalQuantity = (float) ($item->quantity ?? $movedQuantity);
            $releasingItemData = array_merge($itemData, [
                'quantity' => $originalQuantity,
                'remaining_quantity' => $movedQuantity,
            ]);

            $releasingDeviceUids = $this->getReleasingStationDeviceUids();
            foreach ($releasingDeviceUids as $deviceUid) {
                broadcast(new KDSFineDineTransactionEvent(
                    $deviceUid,
                    (object) $transactionData,
                    [$releasingItemData],
                    true
                ));
            }
        }

        // Broadcast to source station device to update/remove
        $sourceDeviceUid = $this->getDeviceUidForStation($fromStationBid);
        if ($sourceDeviceUid) {
            broadcast(new KDSFineDineTransactionEvent(
                $sourceDeviceUid,
                (object) $transactionData,
                [$itemData],
                false
            ));
        }
    }

    // DEVICE RESOLUTION HELPERS

    private function getDeviceUidForStation(?string $stationBid): ?string
    {
        if (!$stationBid) {
            return null;
        }

        return DeviceSettings::where('kitchen_station_bid', $stationBid)
            ->where('device_type', DeviceType::KDS)
            ->value('device_uid');
    }

    private function getNonReleasingStationDeviceUids(): array
    {
        $nonReleasingStationBids = CDISKitchenStation::where('queueing_group_type', '!=', QueueingGroup::RELEASING)
            ->pluck('bid')
            ->toArray();

        if (empty($nonReleasingStationBids)) {
            return [];
        }

        return DeviceSettings::whereIn('kitchen_station_bid', $nonReleasingStationBids)
            ->where('device_type', DeviceType::KDS)
            ->pluck('device_uid')
            ->unique()
            ->filter()
            ->values()
            ->toArray();
    }

    private function getReleasingStationDeviceUids(): array
    {
        $releasingStationBids = CDISKitchenStation::where('queueing_group_type', QueueingGroup::RELEASING)
            ->pluck('bid')
            ->toArray();

        if (empty($releasingStationBids)) {
            return [];
        }

        return DeviceSettings::whereIn('kitchen_station_bid', $releasingStationBids)
            ->where('device_type', DeviceType::KDS)
            ->pluck('device_uid')
            ->unique()
            ->filter()
            ->values()
            ->toArray();
    }

    // UTILITY

    private function errorResult(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'data' => null,
        ];
    }
}
