<?php

namespace App\Services\KitchenDisplay;

use App\Entities\CDISKitchenStation;
use App\Entities\DeviceSettings;
use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Entities\KitchenDisplayMovementHistory;
use App\Enums\API\DeviceType;
use App\Enums\KDS\MenuStatus;
use App\Enums\KDS\QueueingGroup;
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
 * Replaces: KitchenDisplayFineDineService, KitchenDisplayFastFoodService
 */
class KitchenDisplayMovementService
{
    use DatabaseTransaction;

    /**
     * Process a move_order action.
     * All items in the transaction move to the next station.
     *
     * @param array $payload
     * @return array
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
        $terminalNumber = $transaction->terminal_number ?? null;

        if (!$transactionId) {
            return $this->errorResult('transaction_id is required');
        }

        $results = [];
        $released = false;
        $nextStationIndex = null;

        foreach ($items as $itemData) {
            $item = (object) $itemData;
            $result = $this->processItemMovement($item, $transaction, $orderType, $next, $release);
            $results[] = $result;

            if ($result['released']) {
                $released = true;
            }
            if ($result['next_station'] !== null) {
                $nextStationIndex = $result['next_station'];
            }
        }

        return [
            'success' => true,
            'message' => 'KDS movement processed successfully',
            'data' => [
                'action' => 'move_order',
                'released' => $released,
                'next_station' => $nextStationIndex,
                'transaction_id' => $transactionId,
            ],
        ];
    }

    /**
     * Process a move_item action.
     * A single item moves to the next station with optional partial quantity.
     *
     * @param array $payload
     * @return array
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
                'next_station' => $result['next_station'],
                'transaction_id' => $transactionId,
            ],
        ];
    }

    /**
     * Process movement for a single item.
     *
     * @param object $item
     * @param object $transaction
     * @param object $orderType
     * @param bool $next
     * @param bool $forceRelease
     * @return array
     */
    private function processItemMovement(object $item, object $transaction, object $orderType, bool $next, bool $forceRelease): array
    {
        $transactionId = $item->transaction_id ?? $transaction->transaction_id ?? null;
        $transactionProductBid = $item->transaction_product_bid ?? null;
        $productUomPackagingBid = $item->product_uom_packaging_bid ?? $item->product_bid ?? null;
        $terminalNumber = $item->terminal_number ?? $transaction->terminal_number ?? null;
        $currentStationIndex = (int) ($item->kitchen_station_index ?? 1);
        $kitchenStationProcessBid = $item->kitchen_station_process_bid ?? null;
        $movedQuantity = (float) ($item->moved_quantity ?? $item->quantity ?? 0);
        $remainingQuantity = isset($item->remaining_quantity) ? (float) $item->remaining_quantity : null;

        // Find the KitchenDisplayDetail record
        $detail = $this->resolveDetail($transactionId, $transactionProductBid, $productUomPackagingBid, $terminalNumber, $currentStationIndex);

        if (!$detail) {
            Log::warning('KDS Movement: Detail not found', [
                'transaction_id' => $transactionId,
                'transaction_product_bid' => $transactionProductBid,
                'station_index' => $currentStationIndex,
            ]);
            return ['released' => false, 'next_station' => null];
        }

        // Determine next station
        $nextStationIndex = $this->determineNextStation($detail, $kitchenStationProcessBid, $currentStationIndex, $next);

        // If no next station or force release, send to releasing station (0)
        $released = false;
        if ($nextStationIndex === null || $forceRelease) {
            $nextStationIndex = 0;
            $released = true;
        }

        if ($nextStationIndex === 0) {
            $released = true;
        }

        Log::info('KDS Movement: Processing', [
            'detail_bid' => $detail->bid,
            'from_station' => $currentStationIndex,
            'to_station' => $nextStationIndex,
            'moved_quantity' => $movedQuantity,
            'remaining_quantity' => $remainingQuantity,
            'released' => $released,
        ]);

        // Determine if this is a partial or full move
        $isPartialMove = $remainingQuantity !== null && $remainingQuantity > 0 && $movedQuantity < $detail->remaining_quantity;

        return $this->transaction(function () use (
            $detail, $currentStationIndex, $nextStationIndex, $movedQuantity,
            $remainingQuantity, $isPartialMove, $released, $item, $transaction, $orderType, $next
        ) {
            if ($isPartialMove) {
                // Partial move: reduce current station qty, add to next station
                $this->handlePartialMove($detail, $currentStationIndex, $nextStationIndex, $movedQuantity, $remainingQuantity, $released);
            } else {
                // Full move: entire item moves to next station
                $this->handleFullMove($detail, $currentStationIndex, $nextStationIndex, $movedQuantity, $released);
            }

            // Record movement history
            $this->recordMovement(
                $detail->bid,
                $currentStationIndex,
                $nextStationIndex,
                (int) $movedQuantity,
                $released ? 'TO_RELEASING' : ($next ? 'FORWARD' : 'BACKWARD')
            );

            // Broadcast events
            $this->broadcastMovement($detail, $currentStationIndex, $nextStationIndex, $movedQuantity, $released, $item, $transaction, $orderType);

            return ['released' => $released, 'next_station' => $nextStationIndex];
        });
    }

    /**
     * Handle partial quantity move.
     * Reduces current station qty, merges or creates at next station.
     */
    private function handlePartialMove(
        KitchenDisplayDetail $detail,
        int $fromStation,
        int $toStation,
        float $movedQuantity,
        float $remainingQuantity,
        bool $released
    ): void {
        // Update current station remaining quantity
        $detail->update([
            'remaining_quantity' => $remainingQuantity,
        ]);

        // Merge or create at destination station
        $this->mergeOrCreateAtStation($detail, $toStation, $movedQuantity, $released);
    }

    /**
     * Handle full item move.
     * Entire item moves to next station.
     */
    private function handleFullMove(
        KitchenDisplayDetail $detail,
        int $fromStation,
        int $toStation,
        float $movedQuantity,
        bool $released
    ): void {
        // Check if same item already exists in destination
        $existingAtDestination = $this->findExistingAtStation($detail, $toStation);

        if ($existingAtDestination) {
            // Merge quantities: add moved quantity to existing record
            $newQuantity = $existingAtDestination->remaining_quantity + $movedQuantity;
            $existingAtDestination->update([
                'remaining_quantity' => $newQuantity,
            ]);

            // Set current to 0 and mark appropriately
            $detail->update([
                'remaining_quantity' => 0,
                'status' => MenuStatus::DONE,
            ]);
            $detail->delete();
        } else {
            // Move the detail record to next station
            $newStatus = $released ? MenuStatus::RELEASING : MenuStatus::ON_PROCESS;
            $newPosition = ($detail->current_position_in_sequence ?? 0) + 1;

            $detail->update([
                'current_station_index' => $toStation,
                'kitchen_station_index' => $toStation,
                'current_position_in_sequence' => $newPosition,
                'status' => $newStatus,
            ]);
        }
    }

    /**
     * Merge quantity into existing record at station, or create new record.
     * NEVER creates duplicate rows for same transaction + item + station.
     */
    private function mergeOrCreateAtStation(
        KitchenDisplayDetail $sourceDetail,
        int $targetStation,
        float $quantity,
        bool $released
    ): void {
        $existing = $this->findExistingAtStation($sourceDetail, $targetStation);

        if ($existing) {
            // Merge: add quantity to existing record
            $newQuantity = $existing->remaining_quantity + $quantity;
            $existing->update([
                'remaining_quantity' => $newQuantity,
            ]);
        } else {
            // Create new record at destination station
            $newStatus = $released ? MenuStatus::RELEASING : MenuStatus::ON_PROCESS;

            // Get kitchen station bid for the target station
            $kitchenSetup = app()->make(KitchenItemSetupRepository::class)
                ->getKitchenStation($sourceDetail->product_uom_packaging_bid, $targetStation);

            KitchenDisplayDetail::create([
                'head_bid' => $sourceDetail->head_bid,
                'transaction_id' => $sourceDetail->transaction_id,
                'transaction_product_bid' => $sourceDetail->transaction_product_bid,
                'product_uom_packaging_bid' => $sourceDetail->product_uom_packaging_bid,
                'name' => $sourceDetail->name,
                'remaining_quantity' => $quantity,
                'original_quantity' => $sourceDetail->original_quantity,
                'kitchen_station_bid' => $kitchenSetup['station_bid_' . $targetStation] ?? null,
                'kitchen_station_index' => $targetStation,
                'current_station_index' => $targetStation,
                'station_sequence' => $sourceDetail->station_sequence,
                'current_position_in_sequence' => ($sourceDetail->current_position_in_sequence ?? 0) + 1,
                'order_sequence' => $sourceDetail->order_sequence,
                'batch_number' => $sourceDetail->batch_number,
                'status' => $newStatus,
                'usage_type' => $sourceDetail->usage_type,
                'order_type_id' => $sourceDetail->order_type_id,
                'order_type_name' => $sourceDetail->order_type_name,
                'special_request' => $sourceDetail->special_request,
                'is_addon' => $sourceDetail->is_addon,
                'addons' => $sourceDetail->addons,
                'terminal_number' => $sourceDetail->terminal_number,
            ]);
        }
    }

    /**
     * Find existing detail record at a target station for the same transaction item.
     * Uses: transaction_id, transaction_product_bid, station
     */
    private function findExistingAtStation(KitchenDisplayDetail $detail, int $stationIndex): ?KitchenDisplayDetail
    {
        return KitchenDisplayDetail::where('head_bid', $detail->head_bid)
            ->where('transaction_product_bid', $detail->transaction_product_bid)
            ->where('product_uom_packaging_bid', $detail->product_uom_packaging_bid)
            ->where('kitchen_station_index', $stationIndex)
            ->where('bid', '!=', $detail->bid)
            ->first();
    }

    /**
     * Resolve KitchenDisplayDetail from item identifiers.
     */
    private function resolveDetail(
        ?string $transactionId,
        ?string $transactionProductBid,
        ?string $productUomPackagingBid,
        ?string $terminalNumber,
        int $stationIndex
    ): ?KitchenDisplayDetail {
        $query = KitchenDisplayDetail::where('status', MenuStatus::ON_PROCESS);

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

        $query->where('kitchen_station_index', $stationIndex);

        return $query->first();
    }

    /**
     * Determine the next station in the sequence.
     * Uses kitchen_station_process_bid and kitchen_station_index to find next.
     * Returns null if no next station exists (should release).
     */
    private function determineNextStation(
        KitchenDisplayDetail $detail,
        ?string $kitchenStationProcessBid,
        int $currentStationIndex,
        bool $next
    ): ?int {
        // Try to use station_sequence from the detail record
        $sequence = json_decode($detail->station_sequence, true);

        if (is_array($sequence) && !empty($sequence)) {
            $currentPosition = $detail->current_position_in_sequence ?? 0;

            // Find current station position in sequence
            $positionInSequence = array_search($currentStationIndex, $sequence);
            if ($positionInSequence !== false) {
                $currentPosition = $positionInSequence;
            }

            $targetPosition = $next ? $currentPosition + 1 : $currentPosition - 1;

            if (isset($sequence[$targetPosition])) {
                $nextValue = $sequence[$targetPosition];
                // 0 means releasing station
                return $nextValue === 0 ? 0 : $nextValue;
            }

            // No next position in sequence = release
            return null;
        }

        // Fallback: use kitchen_station_process_bid to look up station sequence
        if ($kitchenStationProcessBid) {
            $nextIndex = $next ? $currentStationIndex + 1 : $currentStationIndex - 1;
            $kitchenSetup = app()->make(KitchenItemSetupRepository::class)
                ->getKitchenStation($detail->product_uom_packaging_bid, $nextIndex);

            if ($kitchenSetup && !empty($kitchenSetup)) {
                return $nextIndex;
            }

            // No next station found
            return null;
        }

        // Default: try next sequential station
        $nextIndex = $next ? $currentStationIndex + 1 : $currentStationIndex - 1;

        if ($nextIndex < 1 || $nextIndex > 4) {
            return null;
        }

        // Check if next station exists in setup
        $kitchenSetup = app()->make(KitchenItemSetupRepository::class)
            ->getKitchenStation($detail->product_uom_packaging_bid, $nextIndex);

        if ($kitchenSetup && !empty($kitchenSetup)) {
            return $nextIndex;
        }

        return null;
    }

    /**
     * Record movement in history table.
     */
    private function recordMovement(
        string $detailBid,
        int $fromStation,
        int $toStation,
        int $quantity,
        string $movementType
    ): void {
        $detail = KitchenDisplayDetail::withTrashed()->find($detailBid);

        if (!$detail) {
            return;
        }

        KitchenDisplayMovementHistory::create([
            'detail_bid' => $detailBid,
            'from_station_index' => $fromStation,
            'to_station_index' => $toStation,
            'quantity_moved' => $quantity,
            'movement_type' => $movementType,
            'status_before' => $detail->status,
            'status_after' => $detail->status,
        ]);
    }

    /**
     * Broadcast movement events to affected KDS devices.
     */
    private function broadcastMovement(
        KitchenDisplayDetail $detail,
        int $fromStation,
        int $toStation,
        float $movedQuantity,
        bool $released,
        object $item,
        object $transaction,
        object $orderType
    ): void {
        $transactionData = (array) $transaction;
        $transactionData['kitchen_station_index'] = $toStation;

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
            'kitchen_station_index' => $toStation,
            'status' => $detail->status,
        ];

        if ($released) {
            // Broadcast to all releasing station devices
            $releasingDeviceUids = $this->getReleasingStationDeviceUids();
            foreach ($releasingDeviceUids as $deviceUid) {
                broadcast(new KDSFastFoodTransactionEvent(
                    $deviceUid,
                    (object) $transactionData,
                    [$itemData],
                    true
                ));
            }
        } else {
            // Broadcast to the target station's device
            $kitchenSetup = app()->make(KitchenItemSetupRepository::class)
                ->getKitchenStation($detail->product_uom_packaging_bid, $toStation);

            $deviceUid = $kitchenSetup['device_uid'] ?? null;
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
        $sourceSetup = app()->make(KitchenItemSetupRepository::class)
            ->getKitchenStation($detail->product_uom_packaging_bid, $fromStation);

        $sourceDeviceUid = $sourceSetup['device_uid'] ?? null;
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
            KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->delete();
            $kitchenDisplay->update([
                'status' => 'DONE',
                'completed_at' => now(),
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
                    (int) ($item->kitchen_station_index ?? 1)
                );

                if ($detail) {
                    $detail->update(['status' => MenuStatus::DONE]);
                    $detail->delete();

                    // Check if all items done for the order
                    $remaining = KitchenDisplayDetail::where('head_bid', $detail->head_bid)->exists();
                    if (!$remaining) {
                        KitchenDisplay::where('bid', $detail->head_bid)->update([
                            'status' => 'DONE',
                            'completed_at' => now(),
                        ]);
                    }
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
                'kitchen_station_index' => 0,
                'current_station_index' => 0,
            ]);
            KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->delete();
            $kitchenDisplay->update(['status' => 'RELEASING']);
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
                $detail = $this->resolveDetail(
                    $item->transaction_id ?? $transactionId,
                    $item->transaction_product_bid ?? null,
                    $item->product_uom_packaging_bid ?? $item->product_bid ?? null,
                    $item->terminal_number ?? null,
                    (int) ($item->kitchen_station_index ?? 1)
                );

                if ($detail) {
                    $detail->update([
                        'status' => MenuStatus::RELEASING,
                        'kitchen_station_index' => 0,
                        'current_station_index' => 0,
                    ]);
                    $detail->delete();
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
                    (int) ($item->kitchen_station_index ?? 1)
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
