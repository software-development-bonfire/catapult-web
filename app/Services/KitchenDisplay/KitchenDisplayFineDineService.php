<?php

namespace App\Services\KitchenDisplay;

use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Enums\KDS\MenuStatus;
use App\Events\KDS\FineDine\KDSFineDineItemMoveEvent;
use App\Events\KDS\FineDine\KDSFineDineItemReleaseEvent;
use App\Events\KDS\FineDine\KDSFineDineOrderDoneEvent;
use App\Events\KDS\KDSFineDineTransactionEvent;
use App\Enums\KDS\KDSSystemMode;
use App\Repositories\Contracts\KitchenItemSetupRepository;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\Types\True_;

/**
 * Fine-Dining Kitchen Display Service
 * 
 * Handles orders where items arrive in multiple waves
 * Each batch is processed independently but linked to same order
 * Items are released progressively (not all at once)
 */
class KitchenDisplayFineDineService extends KitchenDisplayService
{
    protected $systemMode = KDSSystemMode::DB_FINE_DINE;

    /**
     * Store Fine-Dine order (first batch)
     * 
     * Fine-Dine: First batch received → Create order with is_partial=true
     * Later batches are added via addBatchToOrder()
     * 
     * @param array $transactionData
     * @return KitchenDisplay|null
     */
    public function storeOrder(array $transactionData): ?KitchenDisplay
    {
        return $this->transaction(function () use ($transactionData) {
            $data = (object) $transactionData;

            $this->log('storeOrder', [
                'order_id' => $data->order_id,
                'transaction_id' => $data->transaction_id,
                'is_partial' => $data->is_partial ?? true,
            ]);

            // Create KitchenDisplay head record
            $kitchenDisplay = KitchenDisplay::create([
                //'bid' => generateUniqueBid('KD'),
                'order_id' => $data->order_id,
                'batch_number' => $data->batch_number ?? 1,
                'transaction_id' => $data->transaction_id,
                'transaction_detail_bid' => $data->transaction_detail_bid,
                'terminal_bid' => $data->terminal_bid,
                'terminal_number' => $data->terminal_number,
                'system_mode' => KDSSystemMode::DB_FINE_DINE,
                'order_type_id' => $data->order_type_id ?? null,
                'order_type_name' => $data->order_type_name ?? 'FINEDINE',
                'is_partial' => $data->is_partial ?? true,
                'is_complete' => false,
                'status' => 'PREPARING',
                'total_items' => count($data->products ?? []),
            ]);

            // Create detail records for products
            $items = [];
            $sequence = 1; // Sequence counter for this batch
            foreach ($data->products as $product) {
                $detail = $this->createDetailRecord($kitchenDisplay, $product, $sequence);
                $items[] = $detail;
                $sequence++;
            }

            // Broadcast to KDS
            $this->broadcastFineDineOrder($kitchenDisplay, $items, 'NEW_ORDER');

            return $kitchenDisplay;
        });
    }

    /**
     * Add batch to existing Fine-Dine order
     * 
     * Called when POS sends additional items for same order_id
     * Creates new items linked to existing order
     * 
     * @param string $orderId
     * @param array $products
     * @param int $batchNumber
     * @return bool
     */
    public function addBatchToOrder(string $orderId, array $products, int $batchNumber = 2): bool
    {
        return $this->transaction(function () use ($orderId, $products, $batchNumber) {
            $this->log('addBatchToOrder', [
                'order_id' => $orderId,
                'batch_number' => $batchNumber,
                'product_count' => count($products),
            ]);

            // Get existing order
            $kitchenDisplay = KitchenDisplay::where('order_id', $orderId)->first();

            if (!$kitchenDisplay) {
                return false; // Order not found
            }

            // Create detail records for new batch
            $items = [];
            foreach ($products as $product) {
                $detail = $this->createDetailRecord($kitchenDisplay, $product, $batchNumber);
                $items[] = $detail;
            }

            // Update total items
            $kitchenDisplay->increment('total_items', count($items));

            // Broadcast new batch
            $this->broadcastFineDineOrder($kitchenDisplay, $items, 'BATCH_ADDED');

            return true;
        });
    }

    /**
     * Mark order as complete (all batches received)
     * 
     * Called when POS sends final batch or explicit completion signal
     * Signals KDS that no more items are coming
     * 
     * @param string $orderId
     * @return bool
     */
    public function completeOrderReceived(string $orderId): bool
    {
        return $this->transaction(function () use ($orderId) {
            $this->log('completeOrderReceived', ['order_id' => $orderId]);

            $kitchenDisplay = KitchenDisplay::where('order_id', $orderId)->first();

            if (!$kitchenDisplay) {
                return false;
            }

            // Mark as complete
            $kitchenDisplay->update([
                'is_partial' => false,
                'is_complete' => true,
            ]);

            // Get all items
            $items = KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->get();

            // Broadcast completion signal to all devices with items from this order
            $deviceUids = $this->getDeviceUidsForOrder($kitchenDisplay->bid);
            foreach ($deviceUids as $deviceUid) {
                broadcast(new KDSFineDineOrderDoneEvent($deviceUid, $kitchenDisplay->toArray(), $items->toArray()));
            }

            return true;
        });
    }

    /**
     * Create detail record for product (Fine-Dine specific)
     * 
     * Fine-Dine: Items don't automatically go to all stations
     * Only move to stations if explicitly moved by operator
     * Sequence shows batch number
     * 
     * @param KitchenDisplay $head
     * @param array $product
     * @param int $batchSequence
     * @return KitchenDisplayDetail
     */
    private function createDetailRecord(
        KitchenDisplay $head,
        array $product,
        int $batchSequence
    ): KitchenDisplayDetail {
        $product = (object) $product;

        // Get kitchen setup for product
        $kitchenSetup = app()->make(KitchenItemSetupRepository::class)
            ->getKitchenStation($product->product_bid, 1);

        $kitchenStationBid = $kitchenSetup['station_bid_1'] ?? null;

        // Check for duplicate: same head + product + station (prevent double-submit)
        $existing = KitchenDisplayDetail::where('head_bid', $head->bid)
            ->where('transaction_product_bid', $product->product_bid)
            ->where('product_uom_packaging_bid', $product->product_bid)
            ->where('kitchen_station_bid', $kitchenStationBid)
            ->where('terminal_number', $head->terminal_number)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Fine-Dine: Start at station 1, but don't auto-move
        $detail = KitchenDisplayDetail::create([
            //'bid' => generateUniqueBid('KDD'),
            'head_bid' => $head->bid,
            'transaction_id' => $product->transaction_id,
            'transaction_product_bid' => $product->product_bid,
            'product_uom_packaging_bid' => $product->product_bid,
            'current_station_index' => 1,
            'kitchen_station_bid' => $kitchenStationBid,
            'original_quantity' => $product->quantity,
            'remaining_quantity' => $product->quantity,
            'station_sequence' => json_encode([1, 2, 3, 0]), // Flexible sequence
            'current_position_in_sequence' => 0,
            'order_sequence' => $batchSequence, // Batch number
            'batch_number' => $head->batch_number,
            'name' => $product->name,
            'usage_type' => $product->usage_type ?? null,
            'special_request' => $product->special_request ?? null,
            'is_addon' => $product->is_addon ?? false,
            'addons' => json_encode($product->addons ?? []),
            'order_type_id' => $product->order_type_id ?? null,
            'order_type_name' => $product->order_type_name ?? 'FINEDINE',
            'terminal_number' => $head->terminal_number,
            'status' => MenuStatus::ON_PROCESS,
        ]);

        return $detail;
    }

    /**
     * Move item to next station
     * Fine-Dine: Items can move independently based on operator action
     * 
     * Accepts payloads:
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
        //return $this->transaction(function () use ($itemData) {
        $data = (object) $itemData;
        $next = $data->next ?? true;
        $quantity = $data->quantity ?? null;
        $remainingQuantity = $data->remaining_quantity ?? null;
        $movedQuantity = $data->moved_quantity ?? null;

        $details = $this->resolveDetails($itemData);

        Log::info('Resolved Details', ['details' => $details]);

        if (empty($details)) {
            $this->log('moveItem:NO_DETAILS_FOUND', (array) $data);
            return false;
        }

        foreach ($details as $detail) {
            Log::info('Processing Detail for Move', ['detail_bid' => $detail->bid, 'current_station_index' => $detail->current_station_index, 'status' => $detail->status]);
            if ($detail->status !== MenuStatus::ON_PROCESS) {
                continue;
            }

            $moveQuantity = $quantity ?? $detail->remaining_quantity;

            // Get next station
            $nextStation = $next
                ? $this->getNextStationInSequence($detail)
                : $this->getPreviousStationInSequence($detail);

            // If there is no next station, treat as release to releasing station
            if ($nextStation === null) {
                $nextStation = 0;
            }

            Log::info('Moving Detail', ['detail_bid' => $detail->bid, 'from_station' => $detail->current_station_index, 'to_station' => $nextStation, 'move_quantity' => $moveQuantity]);
            if ($quantity !== null && $remainingQuantity !== null && $remainingQuantity > 0) {
                // Partial move: keep item at station with reduced qty
                $detail->update([
                    'remaining_quantity' => $remainingQuantity,
                ]);
            } else {
                // Full move: move entire item to next station
                $detail->update([
                    'current_station_index' => $nextStation,
                    'current_position_in_sequence' => $detail->current_position_in_sequence + ($next ? 1 : -1),
                    'status' => $nextStation === 0 ? MenuStatus::RELEASING : MenuStatus::ON_PROCESS,
                ]);
            }

            Log::info('Updated Detail for Move', ['detail_bid' => $detail->bid, 'new_station_index' => $detail->current_station_index, 'new_status' => $detail->status]);
            // Record movement
            $this->recordMovement(
                $detail->bid,
                $detail->current_station_index,
                $nextStation,
                (int) $moveQuantity,
                $next ? 'FORWARD' : 'BACKWARD'
            );

            // Broadcast movement
            $order = KitchenDisplay::find($detail->head_bid);

            if ($nextStation === 0) {
                // Broadcast release event to ALL releasing station devices
                $releasingDeviceUids = $this->getReleasingStationDeviceUids();
                $releasePayload = $this->buildReleaseEventPayload($detail, $order, $itemData);
                Log::info('Broadcasting Release Event to all releasing devices', ['device_uids' => $releasingDeviceUids, 'detail_bid' => $detail->bid, 'order_id' => $order->order_id ?? null]);
                foreach ($releasingDeviceUids as $deviceUid) {
                    broadcast(new KDSFineDineItemReleaseEvent(
                        $deviceUid,
                        $releasePayload['item'],
                        $releasePayload['transaction'],
                        $releasePayload['order_type'],
                        $movedQuantity ?? $moveQuantity
                    ));
                    Log::info('Broadcasted to releasing devices', ['device_uid' => $deviceUid, 'detail' => json_encode($detail), 'moved_quantity' => $moveQuantity ?? 1]);
                }

            } else {
                $deviceUid = $this->getDeviceUidForStation($detail->kitchen_station_bid);
                Log::info('Broadcasting Move Event', ['device_uid' => $deviceUid, 'detail_bid' => $detail->bid, 'order_id' => $order->order_id ?? null]);
                if ($deviceUid) {
                    broadcast(new KDSFineDineItemMoveEvent(
                        $deviceUid,
                        $detail,
                        $order,
                        $detail->current_station_index,
                        $nextStation,
                        $moveQuantity ?? 1
                    ));
                }
            }
        }

        return true;
        //});
    }

    /**
     * Release item (mark as ready)
     * Fine-Dine: Can release individual items as they complete
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

                $order = KitchenDisplay::find($detail->head_bid);
                $releasePayload = $this->buildReleaseEventPayload($detail, $order, $itemData);
                // Broadcast to ALL releasing station devices
                $releasingDeviceUids = $this->getReleasingStationDeviceUids();
                foreach ($releasingDeviceUids as $deviceUid) {
                    broadcast(new KDSFineDineItemReleaseEvent(
                        $deviceUid,
                        $releasePayload['item'],
                        $releasePayload['transaction'],
                        $releasePayload['order_type'],
                        $detail->remaining_quantity ?? 1
                    ));
                    Log::info('Broadcasted to releasing devices', ['device_uid' => $deviceUid, 'detail' => json_encode($detail), 'order_id' => $order->order_id ?? null]);
                }
            }

            return true;
        });
    }

    /**
     * Mark item as done
     * Fine-Dine: Items can be done independently
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

                $order = KitchenDisplay::find($detail->head_bid);
                if ($order) {
                    $order->increment('completed_items');
                }
            }

            return true;
        });
    }

    /**
     * Mark entire order as done
     * Fine-Dine: Only mark done when explicitly called
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
            $txn = isset($data->transaction) ? (object) $data->transaction : $data;

            $terminalNumber = $txn->terminal_number ?? null;
            $transactionId = $txn->transaction_id ?? null;
            $orderId = $data->order_id ?? null;

            $this->log('doneOrder', ['terminal_number' => $terminalNumber, 'transaction_id' => $transactionId]);

            if ($orderId) {
                $kitchenDisplay = KitchenDisplay::where('order_id', $orderId)->first();
            } elseif ($terminalNumber && $transactionId) {
                $kitchenDisplay = KitchenDisplay::where('terminal_number', $terminalNumber)
                    ->where('transaction_id', $transactionId)
                    ->first();
            } else {
                return false;
            }

            if (!$kitchenDisplay) {
                return false;
            }

            $items = KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->get();
            KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->delete();

            $kitchenDisplay->update([
                'status' => 'DONE',
                'completed_at' => now(),
            ]);

            $deviceUids = $this->getDeviceUidsForOrder($kitchenDisplay->bid);
            foreach ($deviceUids as $deviceUid) {
                broadcast(new KDSFineDineOrderDoneEvent($deviceUid, $kitchenDisplay->toArray(), $items->toArray()));
            }

            return true;
        });
    }

    /**
     * Remove item
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
            $txn = isset($data->transaction) ? (object) $data->transaction : $data;

            $terminalNumber = $txn->terminal_number ?? null;
            $transactionId = $txn->transaction_id ?? null;
            $orderId = $data->order_id ?? null;

            $this->log('removeOrder', ['terminal_number' => $terminalNumber, 'transaction_id' => $transactionId]);

            if ($orderId) {
                $kitchenDisplay = KitchenDisplay::where('order_id', $orderId)->first();
            } elseif ($terminalNumber && $transactionId) {
                $kitchenDisplay = KitchenDisplay::where('terminal_number', $terminalNumber)
                    ->where('transaction_id', $transactionId)
                    ->first();
            } else {
                return false;
            }

            if (!$kitchenDisplay) {
                return false;
            }

            KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->delete();
            $kitchenDisplay->delete();

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
            $terminalBid = $txn->terminal_bid ?? null;
            $transactionId = $txn->transaction_id ?? null;

            Log::info('Resolving details from transaction payload', ['terminal_bid' => $terminalBid, 'transaction_id' => $transactionId]);
            if ($terminalBid && $transactionId) {
                $kitchenDisplay = KitchenDisplay::where('terminal_bid', $terminalBid)
                    ->where('transaction_id', $transactionId)
                    ->first();

                Log::info('Found kitchen display for transaction payload', ['kitchen_display' => $kitchenDisplay]);
                Log::info('Found kitchen display bid', ['kitchen_display_bid' => $kitchenDisplay->bid ?? null]);
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

    private function buildReleaseEventPayload(KitchenDisplayDetail $detail, ?KitchenDisplay $order, array $payload): array
    {
        if (!empty($payload['row_item'])) {
            $rowItem = (object) $payload['row_item'];

            return [
                'item' => $rowItem->item ?? $detail,
                'transaction' => $rowItem->transaction ?? $order,
                'order_type' => $rowItem->order_type ?? $detail->order_type_name ?? $order->order_type_name ?? null,
            ];
        }

        return [
            'item' => $payload['item'] ?? $detail,
            'transaction' => $payload['transaction'] ?? $order,
            'order_type' => $payload['order_type'] ?? $detail->order_type_name ?? $order->order_type_name ?? null,
        ];
    }

    /**
     * Broadcast Fine-Dine order to KDS devices
     * 
     * @param KitchenDisplay $order
     * @param array $items
     * @param string $eventType (NEW_ORDER, BATCH_ADDED, ORDER_COMPLETE)
     * @return void
     */
    private function broadcastFineDineOrder(KitchenDisplay $order, array $items, string $eventType): void
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
            broadcast(new KDSFineDineTransactionEvent(
                $deviceUid,
                $order,
                $deviceItems,
                false,
                $eventType
            ));
        }

        // Broadcast to releasing station devices if order is complete
        if ($order->is_complete) {
            $releasingDeviceUids = $this->getDeviceUidsForOrder($order->bid ?? '');
            foreach ($releasingDeviceUids as $deviceUid) {
                if (!isset($itemsByDevice[$deviceUid])) {
                    broadcast(new KDSFineDineTransactionEvent(
                        $deviceUid,
                        $order,
                        $items,
                        true,
                        $eventType
                    ));
                }
            }
        }
    }
}
