<?php

namespace App\Services\KitchenDisplay;

use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Enums\KDS\MenuStatus;
use App\Events\KDS\FastFood\KDSFastFoodItemMoveEvent;
use App\Events\KDS\FastFood\KDSFastFoodItemReleaseEvent;
use App\Events\KDS\FastFood\KDSFastFoodOrderDoneEvent;
use App\Events\KDS\KDSFastFoodTransactionEvent;
use App\Enums\KDS\KDSSystemMode;
use App\Repositories\Contracts\KitchenItemSetupRepository;

/**
 * Fast-Food Kitchen Display Service
 * 
 * Handles orders where entire transaction is received at once
 * Items are completed together and released as complete order
 */
class KitchenDisplayFastFoodService extends KitchenDisplayService
{
    protected $systemMode = KDSSystemMode::DB_FAST_FOOD;

    /**
     * Store Fast-Food order
     * 
     * Fast-Food: Complete order received → All items to station 1 → Move together
     * 
     * @param array $transactionData
     * @return KitchenDisplay|null
     */
    public function storeOrder(array $transactionData): ?KitchenDisplay
    {
        return $this->transaction(function () use ($transactionData) {
            $data = (object) $transactionData;
            
            $this->log('storeOrder', ['transaction_id' => $data->transaction_id]);

            // Create or get KitchenDisplay head record
            $kitchenDisplay = KitchenDisplay::create([
                //'bid' => generateUniqueBid('KD'),
                'transaction_id' => $data->transaction_id,
                'transaction_detail_bid' => $data->transaction_detail_bid,
                'terminal_bid' => $data->terminal_bid,
                'terminal_number' => $data->terminal_number,
                'system_mode' => KDSSystemMode::DB_FAST_FOOD,
                'order_type_id' => $data->order_type_id ?? null,
                'order_type_name' => $data->order_type_name ?? 'FASTFOOD',
                'status' => 'PREPARING',
                'total_items' => count($data->products ?? []),
            ]);

            // Create detail records for each product
            $items = [];
            foreach ($data->products as $product) {
                $detail = $this->createDetailRecord($kitchenDisplay, $product);
                $items[] = $detail;
            }

            // Broadcast to KDS
            $this->broadcastFastFoodOrder($kitchenDisplay, $items);

            return $kitchenDisplay;
        });
    }

    /**
     * Create detail record for product
     * Fast-Food: All items start at station 1
     * Sequence: [1, 2, 3, 0] (through all stations to releasing)
     * 
     * @param KitchenDisplay $head
     * @param array $product
     * @return KitchenDisplayDetail
     */
    private function createDetailRecord(KitchenDisplay $head, array $product): KitchenDisplayDetail
    {
        $product = (object) $product;

        // Get kitchen setup for product
        $kitchenSetup = app()->make(KitchenItemSetupRepository::class)
            ->getKitchenStation($product->product_bid, 1);

        // Fast-Food always starts at station 1
        $detail = KitchenDisplayDetail::create([
            //'bid' => generateUniqueBid('KDD'),
            'head_bid' => $head->bid,
            'transaction_id' => $product->transaction_id,
            'transaction_product_bid' => $product->product_bid,
            'product_uom_packaging_bid' => $product->product_bid,
            'current_station_index' => 1,
            'kitchen_station_bid' => $kitchenSetup['station_bid_1'] ?? null,
            'original_quantity' => $product->quantity,
            'remaining_quantity' => $product->quantity,
            'station_sequence' => json_encode([1, 2, 3, 0]), // Fixed sequence for fast-food
            'current_position_in_sequence' => 0,
            'name' => $product->name,
            'usage_type' => $product->usage_type ?? null,
            'special_request' => $product->special_request ?? null,
            'is_addon' => $product->is_addon ?? false,
            'addons' => json_encode($product->addons ?? []),
            'order_type_id' => $product->order_type_id ?? null,
            'order_type_name' => $product->order_type_name ?? 'FASTFOOD',
            'terminal_number' => $head->terminal_number,
            'status' => MenuStatus::ON_PROCESS,
        ]);

        return $detail;
    }

    /**
     * Move item to next station
     * Fast-Food: Items move through ALL stations (1→2→3→0 releasing)
     * 
     * Accepts payloads in multiple formats:
     * - Direct: { "kitchen_display_detail_bid": "..." }
     * - Order: { "transaction": { "terminal_number": ..., "transaction_id": ... }, "next": true }
     * - Item:  { "item": { "terminal_number": ..., "transaction_id": ..., "product_bid": ... } }
     * - Row:   { "row_item": { "item": { ... } }, "next": true, "quantity": x, "remaining_quantity": y }
     * 
     * @param array $itemData
     * @return bool
     */
    public function moveItem(array $itemData): bool
    {
        return $this->transaction(function () use ($itemData) {
            $data = (object) $itemData;
            $next = $data->next ?? true;
            $quantity = $data->quantity ?? null;
            $remainingQuantity = $data->remaining_quantity ?? null;

            // Resolve KitchenDisplayDetail from payload
            $details = $this->resolveDetails($itemData);

            if (empty($details)) {
                $this->log('moveItem:NO_DETAILS_FOUND', (array) $data);
                return false;
            }

            foreach ($details as $detail) {
                if ($detail->status !== MenuStatus::ON_PROCESS) {
                    continue;
                }

                // Handle partial quantity move
                $moveQuantity = $quantity ?? $detail->remaining_quantity;

                if ($quantity !== null && $remainingQuantity !== null && $remainingQuantity > 0) {
                    // Partial move: split the detail - reduce current, create new at next station
                    $nextStation = $next
                        ? $this->getNextStationInSequence($detail)
                        : $this->getPreviousStationInSequence($detail);

                    if ($nextStation === null) {
                        continue;
                    }

                    // Update current detail with remaining quantity
                    $detail->update([
                        'remaining_quantity' => $remainingQuantity,
                    ]);

                    // Broadcast movement to the target station's device
                    $this->broadcastItemMovement($detail, $nextStation, $moveQuantity, $next);

                    // Record movement
                    $this->recordMovement(
                        $detail->bid,
                        $detail->current_station_index,
                        $nextStation,
                        (int) $moveQuantity,
                        $next ? 'FORWARD' : 'BACKWARD'
                    );
                } else {
                    // Full move: move entire detail to next station
                    $nextStation = $next
                        ? $this->getNextStationInSequence($detail)
                        : $this->getPreviousStationInSequence($detail);

                    if ($nextStation === null) {
                        continue;
                    }

                    $detail->update([
                        'current_station_index' => $nextStation,
                        'current_position_in_sequence' => $detail->current_position_in_sequence + ($next ? 1 : -1),
                        'status' => $nextStation === 0 ? MenuStatus::RELEASING : MenuStatus::ON_PROCESS,
                    ]);

                    // Broadcast movement
                    $this->broadcastItemMovement($detail, $nextStation, $moveQuantity, $next);

                    // Record movement
                    $this->recordMovement(
                        $detail->bid,
                        $detail->current_station_index,
                        $nextStation,
                        (int) $moveQuantity,
                        $next ? 'FORWARD' : 'BACKWARD'
                    );
                }
            }

            return true;
        });
    }

    /**
     * Resolve KitchenDisplayDetail records from various payload formats.
     * 
     * @param array $payload
     * @return array|KitchenDisplayDetail[]
     */
    private function resolveDetails(array $payload): array
    {
        // Direct detail BID
        if (!empty($payload['kitchen_display_detail_bid'])) {
            $detail = KitchenDisplayDetail::find($payload['kitchen_display_detail_bid']);
            return $detail ? [$detail] : [];
        }

        // From row_item payload (per-item with quantity)
        if (!empty($payload['row_item'])) {
            $rowItem = (object) $payload['row_item'];
            $item = (object) ($rowItem->item ?? $payload['row_item']);

            $terminalNumber = $item->terminal_number ?? null;
            $transactionId = $item->transaction_id ?? null;
            $productBid = $item->product_bid ?? null;

            if ($terminalNumber && $transactionId && $productBid) {
                return KitchenDisplayDetail::where('terminal_number', $terminalNumber)
                    ->whereHas('head', function ($q) use ($transactionId) {
                        $q->where('transaction_id', $transactionId);
                    })
                    ->where('product_uom_packaging_bid', $productBid)
                    ->get()
                    ->all();
            }
        }

        // From item payload (per-menu)
        if (!empty($payload['item'])) {
            $item = (object) $payload['item'];
            $terminalNumber = $item->terminal_number ?? null;
            $transactionId = $item->transaction_id ?? null;
            $productBid = $item->product_bid ?? null;

            if ($terminalNumber && $transactionId && $productBid) {
                return KitchenDisplayDetail::where('terminal_number', $terminalNumber)
                    ->whereHas('head', function ($q) use ($transactionId) {
                        $q->where('transaction_id', $transactionId);
                    })
                    ->where('product_uom_packaging_bid', $productBid)
                    ->get()
                    ->all();
            }
        }

        // From transaction payload (per-order - move all items)
        if (!empty($payload['transaction'])) {
            $txn = (object) $payload['transaction'];
            $terminalNumber = $txn->terminal_number ?? null;
            $transactionId = $txn->transaction_id ?? null;

            if ($terminalNumber && $transactionId) {
                $kitchenDisplay = KitchenDisplay::where('terminal_number', $terminalNumber)
                    ->where('transaction_id', $transactionId)
                    ->first();

                if ($kitchenDisplay) {
                    return KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)
                        ->where('status', MenuStatus::ON_PROCESS)
                        ->get()
                        ->all();
                }
            }
        }

        return [];
    }

    /**
     * Broadcast item movement to the target station's KDS device.
     * 
     * @param KitchenDisplayDetail $detail
     * @param int $nextStation
     * @param float $quantity
     * @param bool $next
     * @return void
     */
    private function broadcastItemMovement(KitchenDisplayDetail $detail, int $nextStation, float $quantity, bool $next): void
    {
        // Get the device for the target station
        $targetStationBid = $this->getStationBidForIndex($detail, $nextStation);
        $deviceUid = $this->getDeviceUidForStation($targetStationBid);

        if ($deviceUid) {
            broadcast(new KDSFastFoodItemMoveEvent(
                $deviceUid,
                $detail,
                $detail->transaction_id ?? '',
                $detail->current_station_index,
                $nextStation,
                $quantity
            ));
        }

        // Also notify the source station's device to remove/update
        $sourceDeviceUid = $this->getDeviceUidForStation($detail->kitchen_station_bid);
        if ($sourceDeviceUid && $sourceDeviceUid !== $deviceUid) {
            broadcast(new KDSFastFoodItemMoveEvent(
                $sourceDeviceUid,
                $detail,
                $detail->transaction_id ?? '',
                $detail->current_station_index,
                $nextStation,
                $quantity
            ));
        }
    }

    /**
     * Get the kitchen station BID for a given station index in the detail's sequence.
     * 
     * @param KitchenDisplayDetail $detail
     * @param int $stationIndex
     * @return string|null
     */
    private function getStationBidForIndex(KitchenDisplayDetail $detail, int $stationIndex): ?string
    {
        if ($stationIndex === 0) {
            // Releasing station - lookup from device settings
            return null;
        }

        // Get from kitchen item setup
        $setup = app()->make(KitchenItemSetupRepository::class)
            ->getKitchenStation($detail->product_uom_packaging_bid, $stationIndex);

        return $setup["station_bid_{$stationIndex}"] ?? null;
    }

    /**
     * Release item (mark as ready for pickup)
     * Fast-Food: All items released together
     * 
     * @param array $itemData
     * @return bool
     */
    public function releaseItem(array $itemData): bool
    {
        return $this->transaction(function () use ($itemData) {
            $details = $this->resolveDetails($itemData);

            if (empty($details)) {
                return false;
            }

            foreach ($details as $detail) {
                $this->log('releaseItem', ['detail_bid' => $detail->bid]);
                $detail->update(['status' => MenuStatus::RELEASING]);

                $deviceUid = $this->getDeviceUidForStation($detail->kitchen_station_bid);
                if ($deviceUid) {
                    broadcast(new KDSFastFoodItemReleaseEvent($deviceUid, $detail, $detail->transaction_id ?? ''));
                }
            }

            return true;
        });
    }

    /**
     * Mark item as done
     * Fast-Food: Once all items done, order is complete
     * 
     * @param array $itemData
     * @return bool
     */
    public function doneItem(array $itemData): bool
    {
        return $this->transaction(function () use ($itemData) {
            $details = $this->resolveDetails($itemData);

            if (empty($details)) {
                return false;
            }

            foreach ($details as $detail) {
                $this->log('doneItem', ['detail_bid' => $detail->bid]);
                $detail->update(['status' => MenuStatus::DONE]);
                $detail->delete();

                // Check if all items done
                $remainingDetails = KitchenDisplayDetail::where('head_bid', $detail->head_bid)->exists();
                if (!$remainingDetails) {
                    $head = KitchenDisplay::find($detail->head_bid);
                    if ($head) {
                        $head->update([
                            'status' => 'DONE',
                            'completed_at' => now(),
                        ]);
                    }
                }
            }

            return true;
        });
    }

    /**
     * Mark entire order as done
     * Fast-Food: Complete order operation
     * 
     * Accepts: { "source": {...}, "transaction": { "terminal_number": ..., "transaction_id": ... } }
     * 
     * @param array $orderData
     * @return bool
     */
    public function doneOrder(array $orderData): bool
    {
        return $this->transaction(function () use ($orderData) {
            $data = (object) $orderData;
            $txn = (object) ($data->transaction ?? $orderData);

            $terminalNumber = $txn->terminal_number ?? null;
            $transactionId = $txn->transaction_id ?? null;

            $this->log('doneOrder', ['terminal_number' => $terminalNumber, 'transaction_id' => $transactionId]);

            $kitchenDisplay = KitchenDisplay::where('terminal_number', $terminalNumber)
                ->where('transaction_id', $transactionId)
                ->first();

            if (!$kitchenDisplay) {
                return false;
            }

            // Get all items before deletion
            $items = KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->get();

            // Delete all items
            KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->delete();

            // Mark order as done
            $kitchenDisplay->update([
                'status' => 'DONE',
                'completed_at' => now(),
            ]);

            // Broadcast done event to each device that had items from this order
            $deviceUids = $this->getDeviceUidsForOrder($kitchenDisplay->bid);
            foreach ($deviceUids as $deviceUid) {
                broadcast(new KDSFastFoodOrderDoneEvent($deviceUid, $kitchenDisplay->toArray(), $items->toArray()));
            }

            return true;
        });
    }

    /**
     * Remove item
     * Fast-Food: Remove single item
     * 
     * @param array $itemData
     * @return bool
     */
    public function removeItem(array $itemData): bool
    {
        return $this->transaction(function () use ($itemData) {
            $details = $this->resolveDetails($itemData);

            if (empty($details)) {
                return false;
            }

            foreach ($details as $detail) {
                $this->log('removeItem', ['detail_bid' => $detail->bid]);
                $detail->update(['status' => MenuStatus::DELETED]);
                $detail->delete();
            }

            return true;
        });
    }

    /**
     * Remove entire order
     * Fast-Food: Remove all items
     * 
     * Accepts: { "transaction": { "terminal_number": ..., "transaction_id": ... } }
     * 
     * @param array $orderData
     * @return bool
     */
    public function removeOrder(array $orderData): bool
    {
        return $this->transaction(function () use ($orderData) {
            $data = (object) $orderData;
            $txn = (object) ($data->transaction ?? $orderData);

            $terminalNumber = $txn->terminal_number ?? null;
            $transactionId = $txn->transaction_id ?? null;

            $this->log('removeOrder', ['terminal_number' => $terminalNumber, 'transaction_id' => $transactionId]);

            $kitchenDisplay = KitchenDisplay::where('terminal_number', $terminalNumber)
                ->where('transaction_id', $transactionId)
                ->first();

            if (!$kitchenDisplay) {
                return false;
            }

            // Delete all items
            KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->delete();

            // Delete order
            $kitchenDisplay->delete();

            return true;
        });
    }

    /**
     * Broadcast Fast-Food order to KDS devices
     * 
     * Groups items by device_uid and broadcasts per-device
     * 
     * @param KitchenDisplay $order
     * @param array $items
     * @return void
     */
    private function broadcastFastFoodOrder(KitchenDisplay $order, array $items): void
    {
        // Group items by device_uid
        $itemsByDevice = [];
        foreach ($items as $item) {
            $setup = app()->make(KitchenItemSetupRepository::class)
                ->getKitchenStation($item->product_uom_packaging_bid, 1);

            $deviceUid = $setup['device_uid'] ?? null;

            if ($deviceUid) {
                if (!isset($itemsByDevice[$deviceUid])) {
                    $itemsByDevice[$deviceUid] = [];
                }
                $itemsByDevice[$deviceUid][] = $item;
            }
        }

        // Broadcast to each device
        foreach ($itemsByDevice as $deviceUid => $deviceItems) {
            broadcast(new KDSFastFoodTransactionEvent(
                $deviceUid,
                $order,
                $deviceItems
            ));
        }

        // Also broadcast to releasing station devices
        $releasingDeviceUids = $this->getDeviceUidsForOrder($order->bid ?? '');
        foreach ($releasingDeviceUids as $deviceUid) {
            if (!isset($itemsByDevice[$deviceUid])) {
                broadcast(new KDSFastFoodTransactionEvent(
                    $deviceUid,
                    $order,
                    $items,
                    true
                ));
            }
        }
    }
}
