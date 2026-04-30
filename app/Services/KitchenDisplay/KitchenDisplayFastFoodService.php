<?php

namespace App\Services\KitchenDisplay;

use App\Entities\CDISTerminal;
use App\Entities\CDISTerminalTransaction;
use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Enums\KDS\MenuStatus;
use App\Events\KDS\FastFood\KDSFastFoodOrderEvent;
use App\Events\KDS\KDSStationEvent;
use App\Events\KDS\Common\KDSOrderDoneEvent;
use App\Events\KDS\KDSDeviceEvent;
use App\Repositories\Contracts\KitchenItemSetupRepository;

/**
 * Fast-Food Kitchen Display Service
 * 
 * Handles orders where entire transaction is received at once
 * Items are completed together and released as complete order
 */
class KitchenDisplayFastFoodService extends KitchenDisplayService
{
    protected $systemMode = 'FASTFOOD';

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
                'system_mode' => 'FASTFOOD',
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

            // Get next station from sequence
            $nextStation = $next
                ? $this->getNextStationInSequence($detail)
                : $this->getPreviousStationInSequence($detail);

            if ($nextStation === null) {
                return false; // Can't move beyond sequence
            }

            // Update detail record
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

            // Broadcast movement to the target station's device
            $deviceUid = $this->getDeviceUidForStation($detail->kitchen_station_bid);
            if ($deviceUid) {
                broadcast(KDSStationEvent::fastFoodMovement($deviceUid, $detail, $nextStation, $data->quantity ?? 0));
            }

            return true;
        });
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
            $detail = KitchenDisplayDetail::find($itemData['kitchen_display_detail_bid']);

            if (!$detail) {
                return false;
            }

            $this->log('releaseItem', ['detail_bid' => $detail->bid]);

            // Mark as releasing
            $detail->update(['status' => MenuStatus::RELEASING]);

            // Broadcast release to the item's device
            $deviceUid = $this->getDeviceUidForStation($detail->kitchen_station_bid);
            if ($deviceUid) {
                broadcast(KDSStationEvent::fastFoodRelease($deviceUid, $detail));
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
            $detail = KitchenDisplayDetail::find($itemData['kitchen_display_detail_bid']);

            if (!$detail) {
                return false;
            }

            $this->log('doneItem', ['detail_bid' => $detail->bid]);

            // Mark as done
            $detail->update(['status' => MenuStatus::DONE]);
            $detail->delete();

            // Check if all items done
            $remainingDetails = KitchenDisplayDetail::where('head_bid', $detail->head_bid)->exists();

            if (!$remainingDetails) {
                // All items done, mark order complete
                KitchenDisplay::find($detail->head_bid)->update([
                    'status' => 'DONE',
                    'completed_at' => now(),
                ]);
            }

            return true;
        });
    }

    /**
     * Mark entire order as done
     * Fast-Food: Complete order operation
     * 
     * @param array $orderData
     * @return bool
     */
    public function doneOrder(array $orderData): bool
    {
        return $this->transaction(function () use ($orderData) {
            $data = (object) $orderData;

            $this->log('doneOrder', ['transaction_id' => $data->transaction_id]);

            $kitchenDisplay = KitchenDisplay::where([
                'transaction_id' => $data->transaction_id,
                'terminal_bid' => $data->terminal_bid,
            ])->first();

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
                broadcast(new KDSOrderDoneEvent($deviceUid, $items->toArray(), $kitchenDisplay->toArray(), 'fastfood'));
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
     * Fast-Food: Remove all items
     * 
     * @param array $orderData
     * @return bool
     */
    public function removeOrder(array $orderData): bool
    {
        return $this->transaction(function () use ($orderData) {
            $data = (object) $orderData;

            $this->log('removeOrder', ['kitchen_display_bid' => $data->kitchen_display_bid]);

            $kitchenDisplay = KitchenDisplay::find($data->kitchen_display_bid);

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
            broadcast(new KDSFastFoodOrderEvent(
                $deviceUid,
                $order,
                $deviceItems
            ));
        }

        // Also broadcast to order type (for releasing station)
        broadcast(new KDSDeviceEvent(
            $order->order_type_id,
            $order,
            $items,
            $order->order_type_name
        ));
    }
}
