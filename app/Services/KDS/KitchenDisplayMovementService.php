<?php

namespace App\Services\KDS;

use App\Entities\CDISKitchenStation;
use App\Entities\DeviceSettings;
use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Entities\KitchenDisplayMovementHistory;
use App\Enums\API\DeviceType;
use App\Enums\KDS\MenuStatus;
use App\Enums\KDS\QueueingGroup;
use App\Events\KDS\FastFood\KDSFastFoodOrderMoveEvent;
use App\Events\KDS\KDSFastFoodTransactionEvent;
use App\Repositories\Contracts\KitchenItemSetupRepository;
use App\Traits\DatabaseTransaction;
use Illuminate\Support\Facades\Log;

/**
 * Unified Kitchen Display Movement Service
 *
 * Handles all KDS movement operations for both move_order and move_item actions.
 * Uses standardized payload structure from Flutter KDS clients.
 *
 * Station identification uses kitchen_station_bid (not index-based).
 * Station sequence is resolved dynamically from KitchenItemSetupRepository.
 */
class KitchenDisplayMovementService
{
    use DatabaseTransaction;

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

        $results = [];
        $released = false;
        $nextStationBid = null;

        foreach ($items as $itemData) {
            $item = (object) $itemData;
            $result = $this->processItemMovement($item, $transaction, $orderType, $next, $release);
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
            'message' => 'KDS movement processed successfully',
            'data' => [
                'action' => 'move_order',
                'released' => $released,
                'next_station_bid' => $nextStationBid,
                'transaction_id' => $transactionId,
            ],
        ];
    }

    /**
     * Process a move_item action.
     * A single item moves to the next station with optional partial quantity.
     */
    public function moveItem(array $payload): array
    {
        $data = (object) ($payload['data'] ?? []);
        $items = $data->items ?? [];
        $transaction = (object) ($data->transaction ?? []);
        $orderType = (object) ($data->order_type ?? []);
        $next = $payload['next'] ?? true;
        $quantity = $payload['quantity'] ?? null;
        $remainingQuantity = $payload['remaining_quantity'] ?? null;
        $movedQuantity = $payload['moved_quantity'] ?? null;
        $release = $payload['release'] ?? false;

        if (empty($items) || empty((array) $transaction)) {
            return $this->errorResult('Invalid payload: items and transaction are required');
        }

        $transactionId = $transaction->transaction_id ?? null;

        if (!$transactionId) {
            return $this->errorResult('transaction_id is required');
        }

        // For move_item, only one item in the array
        $item = (object) $items[0];

        // Override quantities from payload level if provided
        if ($movedQuantity !== null) {
            $item->moved_quantity = $movedQuantity;
        }
        if ($remainingQuantity !== null) {
            $item->remaining_quantity = $remainingQuantity;
        }

        $result = $this->processItemMovement($item, $transaction, $orderType, $next, $release);

        return [
            'success' => true,
            'message' => 'KDS movement processed successfully',
            'data' => [
                'action' => 'move_item',
                'released' => $result['released'],
                'next_station_bid' => $result['next_station_bid'],
                'transaction_id' => $transactionId,
            ],
        ];
    }

    /**
     * Process movement for a single item.
     */
    private function processItemMovement(object $item, object $transaction, object $orderType, bool $next, bool $forceRelease): array
    {
        $transactionId = $item->transaction_id ?? $transaction->transaction_id ?? null;
        $transactionProductBid = $item->transaction_product_bid ?? null;
        $productUomPackagingBid = $item->product_uom_packaging_bid ?? $item->product_bid ?? null;
        $terminalNumber = $item->terminal_number ?? $transaction->terminal_number ?? null;
        $currentStationBid = $item->kitchen_station_bid ?? null;
        $movedQuantity = (float) ($item->moved_quantity ?? $item->quantity ?? 0);
        $remainingQuantity = isset($item->remaining_quantity) ? (float) $item->remaining_quantity : null;

        // Find the KitchenDisplayDetail record
        $detail = $this->resolveDetail($transactionId, $transactionProductBid, $productUomPackagingBid, $terminalNumber, $currentStationBid);

        if (!$detail) {
            Log::warning('KDS Movement: Detail not found', [
                'transaction_id' => $transactionId,
                'transaction_product_bid' => $transactionProductBid,
                'kitchen_station_bid' => $currentStationBid,
            ]);
            return ['released' => false, 'next_station_bid' => null];
        }

        // If client sends 0 for moved_quantity (full-move call e.g. moveOrder),
        // use the detail's current remaining quantity
        if ($movedQuantity <= 0) {
            $movedQuantity = $detail->remaining_quantity ?? 0;
        }

        // Determine next station
        $nextStationBid = $this->determineNextStation($detail, $next);

        // If no next station or force release, send to releasing (null station_bid)
        $released = false;
        if ($nextStationBid === null || $forceRelease) {
            $nextStationBid = null;
            $released = true;
        }

        Log::info('KDS Movement: Processing', [
            'detail_bid' => $detail->bid,
            'from_station_bid' => $currentStationBid,
            'to_station_bid' => $nextStationBid,
            'moved_quantity' => $movedQuantity,
            'remaining_quantity' => $remainingQuantity,
            'released' => $released,
        ]);

        // Determine if this is a partial or full move
        $isPartialMove = $remainingQuantity !== null && $remainingQuantity > 0 && $movedQuantity < $detail->remaining_quantity;

        // For a full move always broadcast with the detail's actual remaining quantity.
        // This prevents stale moved_quantity values (e.g. from Flutter's local DB after a
        // prior partial move) from producing an incorrect broadcast quantity.
        if (!$isPartialMove) {
            $movedQuantity = $detail->remaining_quantity ?? $movedQuantity;
        }

        return $this->transaction(function () use (
            $detail, $currentStationBid, $nextStationBid, $movedQuantity,
            $remainingQuantity, $isPartialMove, $released, $item, $transaction, $orderType, $next
        ) {
            if ($isPartialMove) {
                $this->handlePartialMove($detail, $nextStationBid, $movedQuantity, $remainingQuantity, $released);
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

    /**
     * Handle partial quantity move.
     * Reduces current detail qty, merges or creates at next station.
     */
    private function handlePartialMove(
        KitchenDisplayDetail $detail,
        ?string $toStationBid,
        float $movedQuantity,
        float $remainingQuantity,
        bool $released
    ): void {
        // Update current record remaining quantity
        $detail->update([
            'remaining_quantity' => $remainingQuantity,
        ]);

        // Merge or create at destination station
        $this->mergeOrCreateAtStation($detail, $toStationBid, $movedQuantity, $released);
    }

    /**
     * Handle full item move.
     * Entire item moves to next station.
     */
    private function handleFullMove(
        KitchenDisplayDetail $detail,
        ?string $toStationBid,
        float $movedQuantity,
        bool $released
    ): void {
        // Check if same item already exists in destination
        $existingAtDestination = $this->findExistingAtStation($detail, $toStationBid);

        if ($existingAtDestination) {
            // Merge quantities into existing destination record
            $newQuantity = $existingAtDestination->remaining_quantity + $movedQuantity;
            $existingAtDestination->update([
                'remaining_quantity' => $newQuantity,
            ]);

            // Mark current record as done – all quantity has left this station
            $detail->update([
                'remaining_quantity' => 0,
                'status' => MenuStatus::DONE,
                'end_at' => now(),
            ]);
            $detail->delete();
        } else {
            // Move the detail record to the next station
            $newStatus = $released ? MenuStatus::RELEASING : MenuStatus::ON_PROCESS;

            $detail->update([
                'kitchen_station_bid' => $toStationBid,
                'status' => $newStatus,
                'started_at' => now(), // item has arrived at new station
                'end_at' => null,      // reset end marker for new station
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
            $newQuantity = $existing->remaining_quantity + $quantity;
            $existing->update([
                'remaining_quantity' => $newQuantity,
            ]);
        } else {
            $newStatus = $released ? MenuStatus::RELEASING : MenuStatus::ON_PROCESS;

            KitchenDisplayDetail::create([
                'head_bid' => $sourceDetail->head_bid,
                'transaction_product_bid' => $sourceDetail->transaction_product_bid,
                'product_uom_packaging_bid' => $sourceDetail->product_uom_packaging_bid,
                'transaction_id' => $sourceDetail->transaction_id,
                'remaining_quantity' => $quantity,
                'kitchen_station_bid' => $targetStationBid,
                'status' => $newStatus,
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
            //->where('bid', '!=', $detail->bid)
            ->first();
    }

    /**
     * Resolve KitchenDisplayDetail from item identifiers.
     *
     * @param array|null $statuses Statuses to filter by; defaults to [ON_PROCESS].
     */
    private function resolveDetail(
        ?string $transactionId,
        ?string $transactionProductBid,
        ?string $productUomPackagingBid,
        ?string $terminalNumber,
        ?string $kitchenStationBid,
        ?array $statuses = null
    ): ?KitchenDisplayDetail {
        $statuses = $statuses ?? [MenuStatus::ON_PROCESS];
        $query = KitchenDisplayDetail::whereIn('status', $statuses);

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
     * Looks up the product's configured stations and finds the next one after current.
     * Returns null if no next station exists (should release).
     */
    private function determineNextStation(KitchenDisplayDetail $detail, bool $next): ?string
    {
        $productBid = $detail->product_uom_packaging_bid;
        $currentStationBid = $detail->kitchen_station_bid;

        // Build the station sequence for this product
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

        // Find current position in sequence
        $currentPos = array_search($currentStationBid, $stations);

        if ($currentPos === false) {
            // Current station not in sequence, default to releasing
            return null;
        }

        $targetPos = $next ? $currentPos + 1 : $currentPos - 1;

        if ($targetPos < 0 || $targetPos >= count($stations)) {
            // Beyond sequence = release
            return null;
        }

        return $stations[$targetPos];
    }

    /**
     * Update the completed_quantity on the head record.
     */
    private function updateHeadCompletedQuantity(string $headBid): void
    {
        $head = KitchenDisplay::find($headBid);
        if (!$head) {
            return;
        }

        // completed = items that are DONE or soft-deleted
        $completedQty = KitchenDisplayDetail::withTrashed()
            ->where('head_bid', $headBid)
            ->where('status', MenuStatus::DONE)
            ->sum('remaining_quantity');

        // Also count releasing items
        $releasingQty = KitchenDisplayDetail::withTrashed()
            ->where('head_bid', $headBid)
            ->where('status', MenuStatus::RELEASING)
            ->sum('remaining_quantity');

        $head->update([
            'completed_quantity' => $completedQty + $releasingQty,
        ]);

        // Auto-complete if no active items remain
        $activeCount = KitchenDisplayDetail::where('head_bid', $headBid)
            ->where('status', MenuStatus::ON_PROCESS)
            ->count();

        if ($activeCount === 0 && !$head->completed_at) {
            $head->update(['completed_at' => now()]);
        }
    }

    /**
     * Record movement in history table.
     */
    private function recordMovement(
        string $detailBid,
        ?string $fromStationBid,
        ?string $toStationBid,
        float $quantity,
        string $movementType
    ): void {
        $detail = KitchenDisplayDetail::withTrashed()->find($detailBid);

        if (!$detail) {
            return;
        }

        // Resolve station indices for history (for reporting)
        $fromIndex = $this->resolveStationIndex($fromStationBid);
        $toIndex = $toStationBid ? $this->resolveStationIndex($toStationBid) : 0;

        KitchenDisplayMovementHistory::create([
            'detail_bid' => $detailBid,
            'from_station_index' => $fromIndex,
            'to_station_index' => $toIndex,
            'quantity_moved' => $quantity,
            'movement_type' => $movementType,
            'status_before' => $detail->status,
            'status_after' => $detail->status,
        ]);
    }

    /**
     * Resolve a station_bid to its index number (1-4) for history tracking.
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
        bool $next = true
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
                // Send-back from releasing station → notify all non-releasing stations
                // so they can restore the items in their local DB.
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
                // Forward to releasing station display
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
            // Broadcast to the target station's device
            $deviceUid = $this->getDeviceUidForStation($toStationBid);
            if ($deviceUid) {
                broadcast(new KDSFastFoodTransactionEvent(
                    $deviceUid,
                    (object) $transactionData,
                    [$itemData],
                    false
                ));
            }
        }

        // Also broadcast to source station device to update/remove
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

    /**
     * Process done_order action.
     */
    public function doneOrder(array $payload): array
    {
        $data = (object) ($payload['data'] ?? $payload);
        $transaction = (object) ($data->transaction ?? $payload['transaction'] ?? []);

        $transactionId = $transaction->transaction_id ?? null;
        $terminalBid = $transaction->terminal_bid ?? null;

        if (!$transactionId) {
            return $this->errorResult('transaction_id is required');
        }

        $kitchenDisplay = KitchenDisplay::where('transaction_id', $transactionId);
        if ($terminalBid) {
            $kitchenDisplay->where('terminal_bid', $terminalBid);
        }
        $kitchenDisplay = $kitchenDisplay->first();

        if (!$kitchenDisplay) {
            return $this->errorResult('Order not found');
        }

        $this->transaction(function () use ($kitchenDisplay) {
            KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->update([
                'status' => MenuStatus::DONE,
            ]);
            KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->delete();
            $kitchenDisplay->update([
                'completed_at' => now(),
                'completed_quantity' => $kitchenDisplay->total_quantity,
            ]);
        });

        return [
            'success' => true,
            'message' => 'Order marked as done',
            'data' => [
                'action' => 'done_order',
                'transaction_id' => $transactionId,
            ],
        ];
    }

    /**
     * Process done_item (done_menu) action.
     */
    public function doneItem(array $payload): array
    {
        $data = (object) ($payload['data'] ?? $payload);
        $items = $data->items ?? [];
        $transaction = (object) ($data->transaction ?? $payload['transaction'] ?? []);

        $transactionId = $transaction->transaction_id ?? null;

        if (empty($items)) {
            return $this->errorResult('items array is required');
        }

        $this->transaction(function () use ($items, $transactionId) {
            foreach ($items as $itemData) {
                $item = (object) $itemData;
                $detail = $this->resolveDetail(
                    $item->transaction_id ?? $transactionId,
                    $item->transaction_product_bid ?? null,
                    $item->product_uom_packaging_bid ?? $item->product_bid ?? null,
                    $item->terminal_number ?? null,
                    $item->kitchen_station_bid ?? null
                );

                if ($detail) {
                    $detail->update(['status' => MenuStatus::DONE]);
                    $detail->delete();

                    $this->updateHeadCompletedQuantity($detail->head_bid);
                }
            }
        });

        return [
            'success' => true,
            'message' => 'Item(s) marked as done',
            'data' => [
                'action' => 'done_item',
                'transaction_id' => $transactionId,
            ],
        ];
    }

    /**
     * Process release_order action.
     */
    public function releaseOrder(array $payload): array
    {
        $data = (object) ($payload['data'] ?? $payload);
        $transaction = (object) ($data->transaction ?? $payload['transaction'] ?? []);

        $transactionId = $transaction->transaction_id ?? null;
        $terminalBid = $transaction->terminal_bid ?? null;

        if (!$transactionId) {
            return $this->errorResult('transaction_id is required');
        }

        $kitchenDisplay = KitchenDisplay::where('transaction_id', $transactionId);
        if ($terminalBid) {
            $kitchenDisplay->where('terminal_bid', $terminalBid);
        }
        $kitchenDisplay = $kitchenDisplay->first();

        if (!$kitchenDisplay) {
            return $this->errorResult('Order not found');
        }

        $this->transaction(function () use ($kitchenDisplay) {
            KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->update([
                'status' => MenuStatus::RELEASING,
                'kitchen_station_bid' => null,
            ]);
            KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->delete();
            $kitchenDisplay->update(['completed_at' => now()]);
        });

        return [
            'success' => true,
            'message' => 'Order released',
            'data' => [
                'action' => 'release_order',
                'released' => true,
                'transaction_id' => $transactionId,
            ],
        ];
    }

    /**
     * Process release_item (release_menu) action.
     */
    public function releaseItem(array $payload): array
    {
        $data = (object) ($payload['data'] ?? $payload);
        $items = $data->items ?? [];
        $transaction = (object) ($data->transaction ?? $payload['transaction'] ?? []);

        $transactionId = $transaction->transaction_id ?? null;

        if (empty($items)) {
            return $this->errorResult('items array is required');
        }

        $this->transaction(function () use ($items, $transactionId) {
            foreach ($items as $itemData) {
                $item = (object) $itemData;
                // Releasing-station items may already have RELEASING status;
                // include both ON_PROCESS and RELEASING so they are found.
                $detail = $this->resolveDetail(
                    $item->transaction_id ?? $transactionId,
                    $item->transaction_product_bid ?? null,
                    $item->product_uom_packaging_bid ?? $item->product_bid ?? null,
                    $item->terminal_number ?? null,
                    $item->kitchen_station_bid ?? null,
                    [MenuStatus::ON_PROCESS, MenuStatus::RELEASING]
                );

                if ($detail) {
                    $detail->update([
                        'status' => MenuStatus::RELEASING,
                        'end_at' => now(),
                    ]);
                    $detail->delete();
                    $this->updateHeadCompletedQuantity($detail->head_bid);
                }
            }
        });

        return [
            'success' => true,
            'message' => 'Item(s) released',
            'data' => [
                'action' => 'release_item',
                'released' => true,
                'transaction_id' => $transactionId,
            ],
        ];
    }

    /**
     * Process remove_order action.
     */
    public function removeOrder(array $payload): array
    {
        $data = (object) ($payload['data'] ?? $payload);
        $transaction = (object) ($data->transaction ?? $payload['transaction'] ?? []);

        $transactionId = $transaction->transaction_id ?? null;
        $terminalBid = $transaction->terminal_bid ?? null;

        if (!$transactionId) {
            return $this->errorResult('transaction_id is required');
        }

        $kitchenDisplay = KitchenDisplay::where('transaction_id', $transactionId);
        if ($terminalBid) {
            $kitchenDisplay->where('terminal_bid', $terminalBid);
        }
        $kitchenDisplay = $kitchenDisplay->first();

        if (!$kitchenDisplay) {
            return $this->errorResult('Order not found');
        }

        $this->transaction(function () use ($kitchenDisplay) {
            KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->update([
                'status' => MenuStatus::DELETED,
            ]);
            KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->delete();
            $kitchenDisplay->delete();
        });

        return [
            'success' => true,
            'message' => 'Order removed',
            'data' => [
                'action' => 'remove_order',
                'transaction_id' => $transactionId,
            ],
        ];
    }

    /**
     * Process remove_item (remove_menu) action.
     */
    public function removeItem(array $payload): array
    {
        $data = (object) ($payload['data'] ?? $payload);
        $items = $data->items ?? [];
        $transaction = (object) ($data->transaction ?? $payload['transaction'] ?? []);

        $transactionId = $transaction->transaction_id ?? null;

        if (empty($items)) {
            return $this->errorResult('items array is required');
        }

        $this->transaction(function () use ($items, $transactionId) {
            foreach ($items as $itemData) {
                $item = (object) $itemData;
                $detail = $this->resolveDetail(
                    $item->transaction_id ?? $transactionId,
                    $item->transaction_product_bid ?? null,
                    $item->product_uom_packaging_bid ?? $item->product_bid ?? null,
                    $item->terminal_number ?? null,
                    $item->kitchen_station_bid ?? null
                );

                if ($detail) {
                    $detail->update(['status' => MenuStatus::DELETED]);
                    $detail->delete();

                    // Check if order has remaining items
                    $remaining = KitchenDisplayDetail::where('head_bid', $detail->head_bid)->exists();
                    if (!$remaining) {
                        KitchenDisplay::where('bid', $detail->head_bid)->delete();
                    }
                }
            }
        });

        return [
            'success' => true,
            'message' => 'Item(s) removed',
            'data' => [
                'action' => 'remove_item',
                'transaction_id' => $transactionId,
            ],
        ];
    }

    /**
     * Get device UID for a kitchen station bid.
     */
    private function getDeviceUidForStation(?string $stationBid): ?string
    {
        if (!$stationBid) {
            return null;
        }

        return DeviceSettings::where('kitchen_station_bid', $stationBid)
            ->where('device_type', DeviceType::KDS)
            ->value('device_uid');
    }

    /**
     * Get all device UIDs for non-releasing stations.
     */
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

    /**
     * Get all device UIDs for releasing stations.
     */
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

    /**
     * Build error result array.
     */
    private function errorResult(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'data' => null,
        ];
    }
}
