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
use App\Events\KDS\FastFood\KDSFastFoodOrderMoveEvent;
use App\Events\KDS\KDSFastFoodTransactionEvent;
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
            return $this->handleStageMovement($item, $transaction, $orderType, $actionType, $movedQuantity, $remainingQuantity, $next);
        }

        // Legacy inter-station movement
        return $this->handleInterStationMoveItem($item, $transaction, $orderType, $next, $movedQuantity, $remainingQuantity, $release);
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
        bool $next
    ): array {
        $transactionId = $item->transaction_id ?? $transaction->transaction_id ?? null;
        $terminalNumber = $item->terminal_number ?? $transaction->terminal_number ?? null;
        $productBid = $item->product_uom_packaging_bid ?? $item->product_bid ?? null;
        $addons = $item->addons ?? null;
        $kitchenStationBid = $item->kitchen_station_bid ?? null;

        // Resolve the source detail record matching the item's current action_type
        $sourceDetail = $this->resolveDetailByActionType(
            $transactionId,
            $productBid,
            $terminalNumber,
            $kitchenStationBid,
            $actionType,
            $addons
        );

        if (!$sourceDetail) {
            Log::warning('KDSManam: Source detail not found for stage movement', [
                'transaction_id' => $transactionId,
                'product_bid' => $productBid,
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
            $next
        ) {
            switch ($actionType) {
                case KDSActionType::FOR_PREPARE:
                    return $this->stageForPrepare($sourceDetail, $movedQuantity);

                case KDSActionType::FOR_BUMP:
                    return $this->stageForBump($sourceDetail, $movedQuantity);

                case KDSActionType::FOR_RECALL:
                    return $this->stageForRecall($sourceDetail, $movedQuantity);

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

        // Create or update target row at FOR_BUMP stage
        $this->createOrUpdateTargetRow($source, $movedQty, KDSActionType::FOR_BUMP, [
            'remaining_quantity' => $movedQty,
            'prepared_quantity' => $movedQty,
        ]);

        // Record movement history
        $this->recordStageMovement($source->bid, KDSActionType::FOR_PREPARE, KDSActionType::FOR_BUMP, $movedQty);

        return [
            'success' => true,
            'message' => 'Item moved from Prepare to Bump',
            'data' => [
                'action' => 'move_item',
                'action_type' => KDSActionType::FOR_BUMP,
                'moved_quantity' => $movedQty,
            ],
        ];
    }

    /**
     * FOR_BUMP stage action:
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

        // Create or update target row at FOR_RECALL stage
        $this->createOrUpdateTargetRow($source, $movedQty, KDSActionType::FOR_RECALL, [
            'bumped_quantity' => $movedQty,
        ]);

        // Record movement history
        $this->recordStageMovement($source->bid, KDSActionType::FOR_BUMP, KDSActionType::FOR_RECALL, $movedQty);

        return [
            'success' => true,
            'message' => 'Item moved from Bump to Recall',
            'data' => [
                'action' => 'move_item',
                'action_type' => KDSActionType::FOR_RECALL,
                'moved_quantity' => $movedQty,
            ],
        ];
    }

    /**
     * FOR_RECALL stage action:
     * Deducts bumped_quantity from source (FOR_RECALL row).
     * Returns item to FOR_BUMP target row with remaining & prepared quantities restored.
     */
    private function stageForRecall(KitchenDisplayDetail $source, float $movedQty): array
    {
        $newBumped = max(0, (float) $source->bumped_quantity - $movedQty);

        $source->update([
            'bumped_quantity' => $newBumped,
        ]);

        // Create or update target row back at FOR_BUMP stage
        $this->createOrUpdateTargetRow($source, $movedQty, KDSActionType::FOR_BUMP, [
            'remaining_quantity' => $movedQty,
            'prepared_quantity' => $movedQty,
        ]);

        // Record movement history
        $this->recordStageMovement($source->bid, KDSActionType::FOR_RECALL, KDSActionType::FOR_BUMP, $movedQty);

        return [
            'success' => true,
            'message' => 'Item recalled back to Bump',
            'data' => [
                'action' => 'move_item',
                'action_type' => KDSActionType::FOR_BUMP,
                'moved_quantity' => $movedQty,
            ],
        ];
    }

    /**
     * Creates a new target row or updates an existing one for the given action_type.
     * Mirrors Flutter's _createOrUpdateTargetRow.
     */
    private function createOrUpdateTargetRow(
        KitchenDisplayDetail $source,
        float $movedQty,
        int $targetActionType,
        array $incrementFields
    ): void {
        $targetCondition = [
            'head_bid' => $source->head_bid,
            'transaction_product_bid' => $source->transaction_product_bid,
            'product_uom_packaging_bid' => $source->product_uom_packaging_bid,
            'terminal_number' => $source->terminal_number,
            'kitchen_station_bid' => $source->kitchen_station_bid,
            'action_type' => $targetActionType,
        ];

        // Include addons in condition if present
        if ($source->addons) {
            $targetCondition['addons'] = $source->addons;
        }

        $existing = KitchenDisplayDetail::withTrashed()->where($targetCondition)->first();

        if ($existing) {
            // Update existing target row — increment the fields
            $updateData = [];
            foreach ($incrementFields as $field => $value) {
                $currentValue = (float) ($existing->{$field} ?? 0);
                $updateData[$field] = $currentValue + $value;
            }
            $updateData['updated_at'] = now();

            // Restore if soft-deleted
            if ($existing->trashed()) {
                $existing->restore();
            }

            $existing->update($updateData);
        } else {
            // Create new target row
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
                'sent_at' => now(),
                'started_at' => $source->started_at,
                // Quantity fields from increment
                'remaining_quantity' => $incrementFields['remaining_quantity'] ?? 0,
                'prepared_quantity' => $incrementFields['prepared_quantity'] ?? 0,
                'bumped_quantity' => $incrementFields['bumped_quantity'] ?? 0,
                'released_quantity' => 0,
            ];

            // Set stage timestamps
            if ($targetActionType === KDSActionType::FOR_BUMP) {
                $newData['prepared_at'] = now();
            } elseif ($targetActionType === KDSActionType::FOR_RECALL) {
                $newData['bumped_at'] = now();
            }

            KitchenDisplayDetail::create($newData);
        }
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
     */
    private function resolveDetailByActionType(
        ?string $transactionId,
        ?string $productBid,
        ?string $terminalNumber,
        ?string $kitchenStationBid,
        int $actionType,
        ?string $addons = null
    ): ?KitchenDisplayDetail {
        $query = KitchenDisplayDetail::whereIn('status', [MenuStatus::ON_PROCESS, MenuStatus::WAITING])
            ->where('action_type', $actionType);

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

        return $query->first();
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
            'quantity' => $movedQuantity,
            'remaining_quantity' => $detail->remaining_quantity,
            'moved_quantity' => $movedQuantity,
            'prepared_quantity' => $detail->prepared_quantity,
            'bumped_quantity' => $detail->bumped_quantity,
            'released_quantity' => $detail->released_quantity,
            'action_type' => $detail->action_type,
            'usage_type' => $detail->usage_type,
            'special_request' => $detail->special_request,
            'is_addon' => $detail->is_addon,
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
                    broadcast(new KDSFastFoodOrderMoveEvent(
                        $deviceUid,
                        (object) $transactionData,
                        [$itemData],
                        $fromStationBid,
                        null,
                        false,
                        true
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
                    broadcast(new KDSFastFoodTransactionEvent(
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
                broadcast(new KDSFastFoodTransactionEvent(
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
                broadcast(new KDSFastFoodTransactionEvent(
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
            broadcast(new KDSFastFoodTransactionEvent(
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
