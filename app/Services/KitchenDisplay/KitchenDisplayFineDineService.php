<?php

namespace App\Services\KitchenDisplay;

use App\Entities\CDISTerminal;
use App\Entities\CDISTerminalTransaction;
use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Enums\KDS\MenuStatus;
use App\Events\KDS\FineDine\KDSFineDineOrderEvent;
use App\Events\KDS\KDSStationEvent;
use App\Events\KDS\Common\KDSOrderDoneEvent;
use App\Events\KDS\KDSDeviceEvent;
use App\Repositories\Contracts\KitchenItemSetupRepository;

/**
 * Fine-Dining Kitchen Display Service
 * 
 * Handles orders where items arrive in multiple waves
 * Each batch is processed independently but linked to same order
 * Items are released progressively (not all at once)
 */
class KitchenDisplayFineDineService extends KitchenDisplayService
{
    protected $systemMode = 'FINEDINE';

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
                'system_mode' => 'FINEDINE',
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
                broadcast(KDSStationEvent::fineDinePartial($deviceUid, $kitchenDisplay, $items, 'ORDER_COMPLETE'));
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

        // Fine-Dine: Start at station 1, but don't auto-move
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
     * Fine-Dine: Only move items that have been received
     * Items can move independently based on operator action
     * 
     * @param array $itemData
     * @return bool
     */
    public function moveItem(array $itemData): bool
    {
        return $this->transaction(function () use ($itemData) {
            $data = (object) $itemData;
            $next = $data->next ?? true;

            $this->log('moveItem', [
                'detail_bid' => $data->kitchen_display_detail_bid,
                'quantity' => $data->quantity,
                'direction' => $next ? 'FORWARD' : 'BACKWARD',
            ]);

            $detail = KitchenDisplayDetail::find($data->kitchen_display_detail_bid);

            if (!$detail || $detail->status !== MenuStatus::ON_PROCESS) {
                return false;
            }

            // Get next station
            $nextStation = $next
                ? $this->getNextStationInSequence($detail)
                : $this->getPreviousStationInSequence($detail);

            if ($nextStation === null) {
                return false;
            }

            // Update detail
            $detail->update([
                'current_station_index' => $nextStation,
                'current_position_in_sequence' => $detail->current_position_in_sequence + ($next ? 1 : -1),
                'status' => $nextStation === 0 ? MenuStatus::RELEASING : MenuStatus::ON_PROCESS,
            ]);

            // Record movement
            $this->recordMovement(
                $detail->bid,
                $detail->current_station_index,
                $nextStation,
                $data->quantity ?? $detail->remaining_quantity,
                $next ? 'FORWARD' : 'BACKWARD'
            );

            // Get order for broadcast
            $order = KitchenDisplay::find($detail->head_bid);

            // Broadcast movement to the target station's device
            $deviceUid = $this->getDeviceUidForStation($detail->kitchen_station_bid);
            if ($deviceUid) {
                broadcast(KDSStationEvent::fineDineMovement($deviceUid, $detail, $order, $nextStation));
            }

            return true;
        });
    }

    /**
     * Release item (mark as ready)
     * Fine-Dine: Can release individual items as they complete
     * NOT all items together like Fast-Food
     * 
     * @param array $itemData
     * @return bool
     */
    public function releaseItem(array $itemData): bool
    {
        return $this->transaction(function () use ($itemData) {
            $detail = KitchenDisplayDetail::find($itemData['kitchen_display_detail_bid']);

            if (!$detail) {
                return false;
            }

            $this->log('releaseItem', ['detail_bid' => $detail->bid]);

            // Mark as releasing
            $detail->update(['status' => MenuStatus::RELEASING]);

            // Get order
            $order = KitchenDisplay::find($detail->head_bid);

            // Broadcast partial release to the item's device
            $deviceUid = $this->getDeviceUidForStation($detail->kitchen_station_bid);
            if ($deviceUid) {
                broadcast(KDSStationEvent::fineDineRelease($deviceUid, $detail, $order));
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
            $detail = KitchenDisplayDetail::find($itemData['kitchen_display_detail_bid']);

            if (!$detail) {
                return false;
            }

            $this->log('doneItem', ['detail_bid' => $detail->bid]);

            $detail->update(['status' => MenuStatus::DONE]);
            $detail->delete();

            // Update order completed count
            $order = KitchenDisplay::find($detail->head_bid);
            $order->increment('completed_items');

            return true;
        });
    }

    /**
     * Mark entire order as done
     * Fine-Dine: Only mark done when explicitly called
     * 
     * @param array $orderData
     * @return bool
     */
    public function doneOrder(array $orderData): bool
    {
        return $this->transaction(function () use ($orderData) {
            $data = (object) $orderData;

            $this->log('doneOrder', ['order_id' => $data->order_id ?? $data->kitchen_display_bid]);

            $kitchenDisplay = $data->order_id
                ? KitchenDisplay::where('order_id', $data->order_id)->first()
                : KitchenDisplay::find($data->kitchen_display_bid);

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
                broadcast(new KDSOrderDoneEvent($deviceUid, $items->toArray(), $kitchenDisplay->toArray(), 'finedine'));
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
            $detail = KitchenDisplayDetail::find($itemData['kitchen_display_detail_bid']);

            if (!$detail) {
                return false;
            }

            $this->log('removeItem', ['detail_bid' => $detail->bid]);

            $detail->update(['status' => MenuStatus::DELETED]);
            $detail->delete();

            return true;
        });
    }

    /**
     * Remove entire order
     * @param array $orderData
     * @return bool
     */
    public function removeOrder(array $orderData): bool
    {
        return $this->transaction(function () use ($orderData) {
            $data = (object) $orderData;

            $this->log('removeOrder', ['order_id' => $data->order_id ?? $data->kitchen_display_bid]);

            $kitchenDisplay = $data->order_id
                ? KitchenDisplay::where('order_id', $data->order_id)->first()
                : KitchenDisplay::find($data->kitchen_display_bid);

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
            broadcast(new KDSFineDineOrderEvent(
                $deviceUid,
                $order,
                $deviceItems,
                $eventType
            ));
        }

        // Broadcast to order type (for releasing station)
        // Only broadcast to releasing if order is complete and ready
        if ($order->is_complete) {
            broadcast(new KDSDeviceEvent(
                $order->order_type_id,
                $order,
                $items,
                $order->order_type_name
            ));
        }
    }
}
