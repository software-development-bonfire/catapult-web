<?php

namespace App\Services;

use App\Entities\CDISProductUomPackaging;
use App\Entities\CDISTerminal;
use App\Entities\CDISTerminalTransaction;
use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Enums\KDS\KDSMovementType;
use App\Enums\KDS\MenuStatus;
use App\Enums\KDS\OrderType;
use App\Events\KDS\FastFood\KDSFastFoodMenuMoveEvent;
use App\Events\KDS\FastFood\KDSFastFoodMenuReleaseEvent;
use App\Events\KDS\FastFood\KDSFastFoodMenuDoneEvent;
use App\Events\KDS\FastFood\KDSFastFoodMenuRemoveEvent;
use App\Events\KDS\FastFood\KDSFastFoodOrderMoveEvent;
use App\Events\KDS\FastFood\KDSFastFoodOrderReleaseEvent;
use App\Events\KDS\FastFood\KDSFastFoodOrderDoneEvent;
use App\Events\KDS\FastFood\KDSFastFoodOrderRemoveEvent;
use App\Events\KDS\FastFood\KDSFastFoodItemMoveEvent;
use App\Events\KDS\KDSFastFoodTransactionEvent;
use App\Repositories\Contracts\KitchenItemSetupRepository;
use App\Repositories\Contracts\POS\TerminalTransactionRepository;
use App\Traits\DatabaseTransaction;
use Carbon\Carbon;

class KitchenDisplayService
{
    use DatabaseTransaction;


    /**
     * This function will be called in releasing station
     * If the station send request to all KDS  that this ORDER
     * will be flag as DONE and should be deleted on the KDS
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function releaseOrder($data)
    {
        \Illuminate\Support\Facades\Log::alert('releaseOrder: ' . json_encode($data));

        $data = (object) stringToJson($data);
        $transaction = (object) $data->transaction;
        $terminalTransaction = CDISTerminalTransaction::where([
            'transaction_id' => $transaction->transaction_id,
            'terminal_bid' => $transaction->terminal_bid
        ])->with('details')->first();

        if (!$terminalTransaction) {
            \Illuminate\Support\Facades\Log::alert('releaseOrder->transaction: Transaction is no exist on cdis_terminal_transaction');
            return;
        }

        \Illuminate\Support\Facades\Log::alert('releaseOrder->transaction: ' . json_encode($transaction));

        $details = $terminalTransaction->details;
        $releasedItems = [];
        
        foreach ($details as $detail) {
            $kitchenDisplays = KitchenDisplay::where('transaction_detail_bid', $detail->bid)->pluck('bid');

            if ($kitchenDisplays->isNotEmpty()) {
                // Collect items before deletion for broadcasting
                $deletedDetails = KitchenDisplayDetail::whereIn('head_bid', $kitchenDisplays)->get();
                foreach ($deletedDetails as $deletedDetail) {
                    $releasedItems[] = [
                        'bid' => $deletedDetail->product_uom_packaging_bid,
                        'product_bid' => $deletedDetail->product_uom_packaging_bid,
                        'name' => $deletedDetail->name,
                        'quantity' => $deletedDetail->remaining_quantity,
                        'remaining_quantity' => $deletedDetail->remaining_quantity,
                        'transaction_id' => $deletedDetail->transaction_id,
                        'order_type_name' => $deletedDetail->order_type_name,
                        'order_type_id' => $deletedDetail->order_type_id ?? '',
                        'status' => $deletedDetail->status,
                    ];
                }
                
                // Bulk delete KitchenDisplayDetail records
                KitchenDisplayDetail::whereIn('head_bid', $kitchenDisplays)->delete();

                // Bulk delete KitchenDisplay records
                KitchenDisplay::whereIn('bid', $kitchenDisplays)->delete();
            }

            // If releasing KDS sent request to make this transaction RELEASE
            // Broadcast to each device that had items from this order
            $deviceUids = $this->getAffectedDeviceUids($releasedItems);
            foreach ($deviceUids as $deviceUid) {
                broadcast(new KDSFastFoodOrderReleaseEvent($deviceUid, $transaction, $releasedItems));
            }
        }

        return $data;
    }

    /**
     * Release (mark as ready) a specific menu item in kitchen display.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function releaseMenu($data)
    {
        return $this->transaction(function () use ($data) {
            $kitchenDisplayDetail = KitchenDisplayDetail::find($data['kitchen_display_detail_bid']);
            $transaction = null;
            
            // Get transaction info before deletion
            $terminal = CDISTerminal::where('number', $kitchenDisplayDetail->terminal_number)->first();
            if ($terminal) {
                $transaction = CDISTerminalTransaction::where([
                    'transaction_id' => $kitchenDisplayDetail->transaction_id,
                    'terminal_bid' => $terminal->bid
                ])->first();
            }
            
            $releasedItem = [
                'bid' => $kitchenDisplayDetail->product_uom_packaging_bid,
                'product_bid' => $kitchenDisplayDetail->product_uom_packaging_bid,
                'name' => $kitchenDisplayDetail->name,
                'quantity' => $kitchenDisplayDetail->remaining_quantity,
                'remaining_quantity' => $kitchenDisplayDetail->remaining_quantity,
                'transaction_id' => $kitchenDisplayDetail->transaction_id,
                'order_type_name' => $kitchenDisplayDetail->order_type_name,
                'order_type_id' => $kitchenDisplayDetail->order_type_id ?? '',
                'status' => $kitchenDisplayDetail->status,
            ];
            
            $kitchenDisplayDetail->update([
                'status' => MenuStatus::RELEASING
            ]);
            $kitchenDisplayDetail->delete();
            
            // Dispatch release menu event
            if ($transaction) {
                $deviceUid = $this->getDeviceUidForItem($kitchenDisplayDetail->product_uom_packaging_bid, $kitchenDisplayDetail->kitchen_station_index);
                if ($deviceUid) {
                    broadcast(new KDSFastFoodMenuReleaseEvent($deviceUid, $releasedItem, $releasedItem['quantity'] ?? 0));
                }
            }

            return true;
        });
    }

    public function doneOrder($data)
    {
        \Illuminate\Support\Facades\Log::alert('doneOrder: ' . json_encode($data));

        $data = (object) stringToJson($data);
        $transaction = (object) $data->transaction;
        $terminalTransaction = CDISTerminalTransaction::where([
            'transaction_id' => $transaction->transaction_id,
            'terminal_bid' => $transaction->terminal_bid
        ])->with('details')->first();

        if (!$terminalTransaction) {
            \Illuminate\Support\Facades\Log::alert('doneOrder->transaction: Transaction is no exist on cdis_terminal_transaction');
            return;
        }

        \Illuminate\Support\Facades\Log::alert('doneOrder->transaction: ' . json_encode($transaction));

        $details = $terminalTransaction->details;
        $completedItems = [];
        
        foreach ($details as $detail) {
            $kitchenDisplays = KitchenDisplay::where('transaction_detail_bid', $detail->bid)->pluck('bid');

            if ($kitchenDisplays->isNotEmpty()) {
                // Collect items before deletion for broadcasting
                $deletedDetails = KitchenDisplayDetail::whereIn('head_bid', $kitchenDisplays)->get();
                foreach ($deletedDetails as $deletedDetail) {
                    $completedItems[] = [
                        'bid' => $deletedDetail->product_uom_packaging_bid,
                        'product_bid' => $deletedDetail->product_uom_packaging_bid,
                        'name' => $deletedDetail->name,
                        'quantity' => $deletedDetail->remaining_quantity,
                        'remaining_quantity' => $deletedDetail->remaining_quantity,
                        'transaction_id' => $deletedDetail->transaction_id,
                        'order_type_name' => $deletedDetail->order_type_name,
                        'order_type_id' => $deletedDetail->order_type_id ?? '',
                        'status' => $deletedDetail->status,
                    ];
                }
                
                // Bulk delete KitchenDisplayDetail records
                KitchenDisplayDetail::whereIn('head_bid', $kitchenDisplays)->delete();

                // Bulk delete KitchenDisplay records
                KitchenDisplay::whereIn('bid', $kitchenDisplays)->delete();
            }

            // If releasing KDS sent request to make this transaction DONE
            // Broadcast to each device that had items from this order
            $deviceUids = $this->getAffectedDeviceUids($completedItems);
            foreach ($deviceUids as $deviceUid) {
                broadcast(new KDSFastFoodOrderDoneEvent($deviceUid, $transaction, $completedItems));
            }
        }

        return $data;
    }


    /**
     * This function will be called in releasing station
     * If the station send request to all KDS  that this Menu Item
     * will be flag as DONE and should be deleted on the KDS
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function doneMenu($data)
    {
        $data = (object) stringToJson($data);
        $item = (object) $data->item;
        $kitchenDisplayDetails = KitchenDisplayDetail::where([
            'transaction_id' => $item->transaction_id,
            'transaction_product_bid' => $item->transaction_product_bid,
            'product_uom_packaging_bid' => $item->product_uom_packaging_bid,
            'terminal_number' => $item->terminal_number,
        ])->get();

        if ($kitchenDisplayDetails->isNotEmpty()) {

            foreach ($kitchenDisplayDetails as $detail) {
                $headBid = $detail->head_bid;

                // Delete the detail record
                $detail->delete();

                // Check if there are any remaining details for the same head_bid
                $remainingDetails = KitchenDisplayDetail::where('head_bid', $headBid)->exists();

                if (!$remainingDetails) {
                    // No more associated detail records, delete the KitchenDisplay
                    KitchenDisplay::where('bid', $headBid)->delete();
                }
            }
            // Dispatch specific event for menu completion
            $deviceUid = $this->getDeviceUidForItem($item->product_uom_packaging_bid, $item->kitchen_station_index ?? 1);
            if ($deviceUid) {
                broadcast(new KDSFastFoodMenuDoneEvent($deviceUid, $item, $item->quantity ?? 1));
            }
        }

        return $data;
    }

    /**
     * Move order to other station.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function moveOrder($data)
    {
        $data = (object) stringToJson($data);
        $transaction = (object) $data->transaction;
        $next = $data->next; // Target to NEXT station otherwise on previous kitchen index
        $terminalTransaction = CDISTerminalTransaction::where([
            'transaction_id' => $transaction->transaction_id,
            'terminal_bid' => $transaction->terminal_bid
        ])->with('details')->first();

        if (!$terminalTransaction) {
            return;
        }

        $details = $terminalTransaction->details;
        $movedItems = [];
        foreach ($details as $detail) {
            $kitchenDisplays = KitchenDisplay::where('transaction_detail_bid', $detail->bid)->get();
            if ($kitchenDisplays->isNotEmpty()) {
                foreach ($kitchenDisplays as $kitchenDisplay) {
                    // Get all kitchen details with specific kitchen station number/index
                    $items = $this->getKitchenDetailsWithStationIndex($transaction, $kitchenDisplay, $kitchenDisplay->bid, $transaction->kitchen_station_index, $next);
                    if (!empty($items)) {
                        $movedItems = array_merge($movedItems, $items);
                    }
                }
            }
        }

        // Dispatch move order event
        if (!empty($movedItems)) {
            $deviceUids = $this->getAffectedDeviceUids($movedItems);
            foreach ($deviceUids as $deviceUid) {
                broadcast(new KDSFastFoodOrderMoveEvent(
                    $deviceUid,
                    $transaction,
                    $movedItems,
                    $transaction->kitchen_station_index,
                    $transaction->kitchen_station_index + 1
                ));
            }
        }

        return $data;
    }


    private function getKitchenDetailsWithStationIndex($transaction, $kitchenDisplay, $kitchenDisplayBid, $index, $next)
    {
        // Get all kitchen details with specific kitchen station number/index
        $kitchenDisplayDetails = KitchenDisplayDetail::where([
            'head_bid' => $kitchenDisplayBid,
            'kitchen_station_index' => $index,
            'status' => MenuStatus::ON_PROCESS,
        ])->get();
        $movedItems = [];
        
        if ($kitchenDisplayDetails->isNotEmpty()) {
            $releasingDetails = [];
            $nextStationDetails = [];

            // Update all remaining QTY to zero to current index,
            foreach ($kitchenDisplayDetails as $kitchenDisplayDetail) {
                $kitchenDisplayDetail = (object) $kitchenDisplayDetail;
                // Check if next station is present then updates the quantity
                $kitchenDisplayDetails2 = KitchenDisplayDetail::where([
                    'head_bid' => $kitchenDisplayDetail['head_bid'],
                    'transaction_product_bid' => $kitchenDisplayDetail['transaction_product_bid'],
                    'product_uom_packaging_bid' => $kitchenDisplayDetail['product_uom_packaging_bid'],
                    'kitchen_station_index' => intval($index) + 1
                ])->first();

                $data = [
                    'bid' => $kitchenDisplayDetail->product_uom_packaging_bid,
                    'product_bid' => $kitchenDisplayDetail->product_uom_packaging_bid,
                    'name' => $kitchenDisplayDetail->name,
                    'quantity' => $kitchenDisplayDetail->remaining_quantity,
                    'remaining_quantity' => $kitchenDisplayDetail->remaining_quantity,
                    'usage_type' => $kitchenDisplayDetail->usage_type,
                    'special_request' => $kitchenDisplayDetail->special_request,
                    'is_addon' => $kitchenDisplayDetail->is_addon,
                    'transaction_id' => $kitchenDisplayDetail->transaction_id,
                    'order_type_name' => $kitchenDisplayDetail->order_type_name,
                    'terminal_number' => $kitchenDisplayDetail->terminal_number,
                    'addons' => $kitchenDisplayDetail->addons,
                    'kitchen_station_index' => $kitchenDisplayDetail->kitchen_station_index,
                    'status' =>  $kitchenDisplayDetail->status,
                ];

                $kitchenOrderStatus = MenuStatus::ON_PROCESS;
                if ($kitchenDisplayDetails2) {
                    // If next station is present then we need to update remaining quantity
                    $kitchenDisplayDetails2->update(['remaining_quantity' => $kitchenDisplayDetail->remaining_quantity]);
                    $kitchenDisplay = app()->make(KitchenItemSetupRepository::class)->getKitchenStation($kitchenDisplayDetail->product_uom_packaging_bid, intval($kitchenDisplayDetail->kitchen_station_index) + 1);
                    $nextStationDetails[] = collect($data)->merge($kitchenDisplay);
                } else {
                    // If no next station then we need to put this on releasing station
                    $kitchenOrderStatus = MenuStatus::RELEASING;
                    $data['status'] = $kitchenOrderStatus;
                    $releasingDetails[] = $data;

                    // Update remaining quantity to 0, because it moves to next stations
                    $kitchenDisplayDetail->update(['remaining_quantity' => 0, 'status' => $kitchenOrderStatus]);
                }
            }

            // Get configured Kitchen Display of each products
            $groupedDisplays = collect($nextStationDetails)->groupBy('device_uid');
            foreach ($groupedDisplays->toArray() as $device => $items) {
                if (! empty($device) && count($items) > 0) {
                    // Broadcast to assigned KDS

                    $transaction->kitchen_station_index = intval($index) + 1;
                    broadcast(new KDSFastFoodTransactionEvent($device, $transaction, $items));
                }
            }
            // Grouped by order type name, then assigned items by order type susch DINE IN, TAKE OUT, DRIVE THRU, etc.
            $groupedReleasingDisplays = collect($releasingDetails)->groupBy('order_type_name');
            foreach ($groupedReleasingDisplays->toArray() as $orderType => $items) {
                if (! empty($orderType) && count($items) > 0) {
                    // Broadcast to assigned KDS for Releasing
                    $deviceUids = $this->getAffectedDeviceUids($items);
                    foreach ($deviceUids as $deviceUid) {
                        broadcast(new KDSFastFoodTransactionEvent($deviceUid, $transaction, $items, true));
                    }
                }
            }
            
            $movedItems = array_merge($nextStationDetails, $releasingDetails);
        } else {
            // Grouped by order type name, then assigned items by order type susch DINE IN, TAKE OUT, DRIVE THRU, etc.
            $groupedReleasingDisplays = collect($kitchenDisplayDetails)->groupBy('order_type_name');
            foreach ($groupedReleasingDisplays->toArray() as $orderType => $items) {
                if (! empty($orderType) && count($items) > 0) {
                    // Broadcast to assigned KDS for Releasing
                    //broadcast(new KDSFastFoodTransactionEvent(...));
                }
            }
        }
        
        return $movedItems;
    }


    /**
     * Move menu item to other station.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function moveMenu($data)
    {
        $data = (object) stringToJson($data);
        $item = (object) $data->item;
        $kitchenDisplayDetails = KitchenDisplayDetail::where([
            'product_uom_packaging_bid' => $item->product_uom_packaging_bid,
            'terminal_number' => $item->terminal_number,
            'kitchen_station_index' => $item->kitchen_station_index,
            'status' => MenuStatus::ON_PROCESS,
        ])->get();

        if ($kitchenDisplayDetails->isNotEmpty()) {
            $releasingDetails = [];
            $nextStationDetails = [];

            // Update all remaining QTY to zero to current index,
            foreach ($kitchenDisplayDetails as $kitchenDisplayDetail) {
                $kitchenDisplayDetail = (object) $kitchenDisplayDetail->toArray();
                // Check if next station is present then updates the quantity
                $kitchenDisplayDetails2 = KitchenDisplayDetail::where([
                    'head_bid' => $kitchenDisplayDetail->head_bid,
                    'transaction_product_bid' => $kitchenDisplayDetail->transaction_product_bid,
                    'product_uom_packaging_bid' => $kitchenDisplayDetail->product_uom_packaging_bid,
                    'kitchen_station_index' => intval($item->kitchen_station_index) + 1
                ])->first();

                $dataItem = [
                    'bid' => $kitchenDisplayDetail->product_uom_packaging_bid,
                    'product_bid' => $kitchenDisplayDetail->product_uom_packaging_bid,
                    'name' => $kitchenDisplayDetail->name,
                    'quantity' => $kitchenDisplayDetail->remaining_quantity,
                    'remaining_quantity' => $kitchenDisplayDetail->remaining_quantity,
                    'usage_type' => $kitchenDisplayDetail->usage_type,
                    'special_request' => $kitchenDisplayDetail->special_request,
                    'is_addon' => $kitchenDisplayDetail->is_addon,
                    'transaction_id' => $kitchenDisplayDetail->transaction_id,
                    'order_type_name' => $kitchenDisplayDetail->order_type_name,
                    'order_type_id' => $kitchenDisplayDetail->order_type_id ?? '',
                    'terminal_number' => $kitchenDisplayDetail->terminal_number,
                    'addons' => $kitchenDisplayDetail->addons,
                    'kitchen_station_index' => $kitchenDisplayDetail->kitchen_station_index,
                    'status' =>  $kitchenDisplayDetail->status,
                ];

                $kitchenOrderStatus = MenuStatus::ON_PROCESS;
                if ($kitchenDisplayDetails2) {

                    // If next station is present then we need to update remaining quantity
                    $kitchenDisplayDetails2->update(['remaining_quantity' => $kitchenDisplayDetail->remaining_quantity]);
                    $kitchenDisplay = app()->make(KitchenItemSetupRepository::class)->getKitchenStation($kitchenDisplayDetail->product_uom_packaging_bid, intval($kitchenDisplayDetail->kitchen_station_index) + 1);
                    if ($kitchenDisplay) {
                        $nextStationDetails[] = collect($dataItem)->merge($kitchenDisplay->toArray());
                    } else {
                        $nextStationDetails[] = $dataItem;
                    }
                } else {
                    // If no next station then we need to put this on releasing station
                    $kitchenOrderStatus = MenuStatus::RELEASING;
                    $dataItem['status'] = $kitchenOrderStatus;
                    $releasingDetails[] = $dataItem;
                }
                // Update remaining quantity to 0, because it moves to next stations
                KitchenDisplayDetail::where('bid', $kitchenDisplayDetail->bid)->update([
                    'remaining_quantity' => 0,
                    'status' => $kitchenOrderStatus
                ]);
            }

            $terminal = CDISTerminal::where('number', $item->terminal_number)->first();
            if (!$terminal) {
                \Illuminate\Support\Facades\Log::warning('Terminal not found for number: ' . $item->terminal_number);
                return $data;
            }
            
            $terminalTransaction = CDISTerminalTransaction::where([
                'transaction_id' => $item->transaction_id,
                'terminal_bid' => $terminal->bid
            ])->with('details')->first();
            
            if (!$terminalTransaction) {
                \Illuminate\Support\Facades\Log::warning('Terminal transaction not found');
                return $data;
            }
            
            $transaction = clone $terminalTransaction;
            $transaction['terminal_number'] = $item->terminal_number;
            $transaction['kitchen_station_index'] = intval($item->kitchen_station_index) + 1;
            
            // Dispatch move menu event with moved items
            $movedItems = array_merge($nextStationDetails, $releasingDetails);
            if (!empty($movedItems)) {
                foreach ($movedItems as $movedItem) {
                    $movedItemArr = is_array($movedItem) ? $movedItem : (array) $movedItem;
                    $deviceUid = $this->getDeviceUidForItem($movedItemArr['product_bid'] ?? '', intval($item->kitchen_station_index) + 1);
                    if ($deviceUid) {
                        broadcast(new KDSFastFoodMenuMoveEvent(
                            $deviceUid,
                            $movedItem,
                            intval($item->kitchen_station_index),
                            intval($item->kitchen_station_index) + 1,
                            $movedItemArr['quantity'] ?? 0
                        ));
                    }
                }
            }
        } else {
            \Illuminate\Support\Facades\Log::warning('No kitchen display details found for movement operation');
        }
        return $data;
    }



    /**
     * Move menu item to other station.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function releaseRowItem($data) {}

    /**
     * Move menu item to other station.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function moveRowItem($data)
    {
        \Illuminate\Support\Facades\Log::alert('moveRowItem: ' . json_encode($data));
        $data = (object) stringToObject($data);
        $rowItem = (object) $data->row_item;
        if ($rowItem) {
            $item = (object) $rowItem->item;
            $transaction = (object) $rowItem->transaction;
            $orderType = (object) $rowItem->order_type;

            // Ensure $item is an object before cloning
            if (is_object($item)) {
                $clonedItem = clone $item;
            } else {
                // Handle the case when $item is not an object (e.g., log an error, return a default value, etc.)
                $clonedItem = $item;
            }

            // Ensure $transaction is an object before cloning
            if (is_object($transaction)) {
                $clonedTransaction = clone $transaction;
            } else {
                // Handle the case when $transaction is not an object (e.g., log an error, return a default value, etc.)
                $clonedTransaction = $transaction;
            }

            $kitchenDisplayDetail = KitchenDisplayDetail::where([
                'transaction_id' => $item->transaction_id,
                'transaction_product_bid' => $item->transaction_product_bid,
                'product_uom_packaging_bid' => $item->product_uom_packaging_bid,
                'terminal_number' => $item->terminal_number,
                'kitchen_station_index' => $item->kitchen_station_index,
                'status' => MenuStatus::ON_PROCESS,
            ])->first();

            if ($kitchenDisplayDetail) {
                $releasingDetails = [];
                $nextStationDetails = [];

                // Convert to object for consistency
                $kitchenDisplayDetail = (object) $kitchenDisplayDetail->toArray();

                $nextStationIndex =  intval($item->kitchen_station_index) + 1;
                $quantityToMove = intval($data->quantity);

                if (toSafeBoolean($data->next, true) == false) {
                    // If not true then it means send back the data (move to previous station)
                    $nextStationIndex =  intval($item->kitchen_station_index) - 1;
                }
                
                // Prevent moving beyond valid station range
                if ($nextStationIndex < 1 || $nextStationIndex > 4) {
                    \Illuminate\Support\Facades\Log::warning('Invalid station index: ' . $nextStationIndex);
                    return $rowItem;
                }

                // Check if next station is present then updates the quantity
                $kitchenDisplayDetails2 = KitchenDisplayDetail::where([
                    'transaction_id' => $item->transaction_id,
                    'transaction_product_bid' => $item->transaction_product_bid,
                    'product_uom_packaging_bid' => $item->product_uom_packaging_bid,
                    'terminal_number' => $item->terminal_number,
                    'kitchen_station_index' => $nextStationIndex
                ])->first();

                $newQuantity = 0;
                $kitchenOrderStatus = MenuStatus::ON_PROCESS;
                
                if ($kitchenDisplayDetails2) {
                    // If next station exists, move quantity there
                    if (toSafeBoolean($data->next, true) == true) {
                        // Moving forward to next station
                        $newQuantity = intval($kitchenDisplayDetails2->remaining_quantity) + $quantityToMove;
                    } else {
                        // Moving backward to previous station
                        $newQuantity = intval($kitchenDisplayDetails2->remaining_quantity) + $quantityToMove;
                    }
                    
                    // Ensure quantity doesn't go negative
                    $newQuantity = max(0, $newQuantity);
                    
                    KitchenDisplayDetail::where('bid', $kitchenDisplayDetails2->bid)->update([
                        'remaining_quantity' => $newQuantity
                    ]);
                    
                    $kitchenDisplay = app()->make(KitchenItemSetupRepository::class)->getKitchenStation($kitchenDisplayDetail->product_uom_packaging_bid, $nextStationIndex);
                    $clonedItem->remaining_quantity = $newQuantity;
                    $clonedItem->quantity = $newQuantity;
                    $clonedItem->kitchen_station_index = $nextStationIndex;
                    $nextStationDetails[] = collect($clonedItem)->merge($kitchenDisplay ?? []);
                } else {
                    // If no next station exists, check if moving to releasing station
                    if ($nextStationIndex > 4) {
                        // Moving to releasing station
                        $kitchenOrderStatus = MenuStatus::RELEASING;
                        $clonedItem->status = $kitchenOrderStatus;
                        $releasingDetails[] = $clonedItem;

                        $kitchenDisplayDetailReleasing = KitchenDisplayDetail::where([
                            'transaction_id' => $item->transaction_id,
                            'transaction_product_bid' => $item->transaction_product_bid,
                            'product_uom_packaging_bid' => $item->product_uom_packaging_bid,
                            'terminal_number' => $item->terminal_number,
                            'kitchen_station_index' => 0
                        ])->first();

                        if ($kitchenDisplayDetailReleasing) {
                            // Update releasing station quantity
                            $newQuantity = intval($kitchenDisplayDetailReleasing->remaining_quantity) + $quantityToMove;
                            $newQuantity = max(0, $newQuantity);
                            KitchenDisplayDetail::where('bid', $kitchenDisplayDetailReleasing->bid)->update([
                                'remaining_quantity' => $newQuantity
                            ]);
                        }
                    }
                }
                
                // Update the current station quantity (reduce by moved quantity)
                $currentRemainingQuantity = intval($kitchenDisplayDetail->remaining_quantity) - $quantityToMove;
                $currentRemainingQuantity = max(0, $currentRemainingQuantity);
                
                KitchenDisplayDetail::where('transaction_id', $item->transaction_id)
                    ->where('transaction_product_bid', $item->transaction_product_bid)
                    ->where('product_uom_packaging_bid', $item->product_uom_packaging_bid)
                    ->where('terminal_number', $item->terminal_number)
                    ->where('kitchen_station_index', $item->kitchen_station_index)
                    ->where('status', MenuStatus::ON_PROCESS)
                    ->update(['remaining_quantity' => $currentRemainingQuantity, 'status' => $kitchenOrderStatus]);

                $clonedTransaction->kitchen_station_index = $nextStationIndex;

                // Get configured Kitchen Display of each products
                if (count($nextStationDetails) > 0) {
                    $groupedDisplays = collect($nextStationDetails)->groupBy('device_uid');
                    foreach ($groupedDisplays->toArray() as $device => $items) {
                        if (! empty($device) && count($items) > 0) {
                            // Broadcast to assigned KDS
                            broadcast(new KDSFastFoodTransactionEvent($device, $clonedTransaction, $items));
                        }
                    }
                }
                
                // Broadcast to assigned KDS for Releasing if applicable
                if (count($releasingDetails) > 0) {
                    $deviceUids = $this->getAffectedDeviceUids(toSafeArray($item));
                    foreach ($deviceUids as $deviceUid) {
                        broadcast(new KDSFastFoodTransactionEvent($deviceUid, $clonedTransaction, toSafeArray($item), true));
                    }
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('Kitchen display detail not found for movement');
            }
        }
        return $rowItem;
    }

    /**
     * Move menu to other station.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function moveItem($data)
    {
        \Illuminate\Support\Facades\Log::alert('moveItem: ' . json_encode($data));
        return $this->transaction(function () use ($data) {
            $kitchenDisplayDetail = KitchenDisplayDetail::find($data['kitchen_display_detail_bid']);

            if ($kitchenDisplayDetail->status == MenuStatus::DONE) {
                return false;
            }

            // Get transaction info for broadcasting
            $terminal = CDISTerminal::where('number', $kitchenDisplayDetail->terminal_number)->first();
            $transaction = null;
            if ($terminal) {
                $transaction = CDISTerminalTransaction::where([
                    'transaction_id' => $kitchenDisplayDetail->transaction_id,
                    'terminal_bid' => $terminal->bid
                ])->first();
            }

            $headBid = $kitchenDisplayDetail->head_bid;
            $transactionProductBid = $kitchenDisplayDetail->transaction_product_bid;
            $remainingQuantity = $kitchenDisplayDetail->remaining_quantity;
            $remainingQuantity = $remainingQuantity - $data['quantity'];
            $isDone = is_null($data['move_station_bid']) || $data['move_station_bid'] == '';

            $hasAssociatedMenu =
                KitchenDisplayDetail::where([
                    'head_bid' => $headBid,
                    ['status', '=', MenuStatus::ON_PROCESS],
                    ['bid', '!=', $data['kitchen_display_detail_bid']]
                ])->count() > 0;

            if (! $hasAssociatedMenu && $isDone) {
                $kitchenDisplayDetail->head()->withTrashed()->update([
                    'completed_at' => Carbon::now()
                ]);
            }

            // Prepare item data for broadcasting
            $movedItem = [
                'bid' => $kitchenDisplayDetail->product_uom_packaging_bid,
                'product_bid' => $kitchenDisplayDetail->product_uom_packaging_bid,
                'name' => $kitchenDisplayDetail->name,
                'quantity' => $data['quantity'],
                'remaining_quantity' => $kitchenDisplayDetail->remaining_quantity,
                'transaction_id' => $kitchenDisplayDetail->transaction_id,
                'order_type_name' => $kitchenDisplayDetail->order_type_name,
                'order_type_id' => $kitchenDisplayDetail->order_type_id ?? '',
                'status' => $kitchenDisplayDetail->status,
            ];

            if ($remainingQuantity > 0) {
                $kitchenDisplayDetail->update([
                    'remaining_quantity' => $remainingQuantity
                ]);
            } else {
                $kitchenDisplayDetail->forceDelete();
            }

            $expectedDestinationData = [
                'transaction_product_bid' => $transactionProductBid,
                'head_bid' => $headBid,
                'kitchen_station_bid' => $data['move_station_bid']
            ];

            $destinationKitchenDisplayDetail = KitchenDisplayDetail::where($expectedDestinationData);

            if ($datum = $destinationKitchenDisplayDetail->first()) {
                $destinationKitchenDisplayDetail->update([
                    'remaining_quantity' => $datum->remaining_quantity + $data['quantity']
                ]);
            } else {
                $expectedDestinationData['remaining_quantity'] = $data['quantity'];
                $expectedDestinationData['status'] = $isDone ? MenuStatus::DONE : MenuStatus::ON_PROCESS;
                $destinationKitchenDisplayDetail->create($expectedDestinationData);
            }

            // Dispatch move item event
            if ($transaction) {
                $deviceUid = $this->getDeviceUidForItem($kitchenDisplayDetail->product_uom_packaging_bid, $kitchenDisplayDetail->kitchen_station_index);
                if ($deviceUid) {
                    broadcast(new KDSFastFoodItemMoveEvent(
                        $deviceUid,
                        $movedItem,
                        $transaction,
                        $kitchenDisplayDetail->kitchen_station_index,
                        $data['move_station_bid'] ?? null,
                        $data['quantity']
                    ));
                }
            }

            return true;
        });
    }

    /**
     * Remove order in kitchen display.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function removeOrder($data)
    {
        return $this->transaction(function () use ($data) {
            $kitchenDisplay = KitchenDisplay::find($data['kitchen_display_bid']);
            $transaction = null;
            $removedItems = [];

            $kitchenDisplay->details()->each(function ($detail) use (&$transaction, &$removedItems) {
                if (!$transaction) {
                    // Get transaction info from first detail
                    $terminal = CDISTerminal::where('number', $detail->terminal_number)->first();
                    if ($terminal) {
                        $transaction = CDISTerminalTransaction::where([
                            'transaction_id' => $detail->transaction_id,
                            'terminal_bid' => $terminal->bid
                        ])->first();
                    }
                }
                
                $removedItems[] = [
                    'bid' => $detail->product_uom_packaging_bid,
                    'product_bid' => $detail->product_uom_packaging_bid,
                    'name' => $detail->name,
                    'quantity' => $detail->remaining_quantity,
                    'remaining_quantity' => $detail->remaining_quantity,
                    'transaction_id' => $detail->transaction_id,
                    'order_type_name' => $detail->order_type_name,
                    'order_type_id' => $detail->order_type_id ?? '',
                    'status' => $detail->status,
                ];
                
                $detail->update([
                    'status' => MenuStatus::DELETED
                ]);

                $detail->delete();
            });

            // Dispatch remove order event
            if ($transaction && !empty($removedItems)) {
                $deviceUids = $this->getAffectedDeviceUids($removedItems);
                foreach ($deviceUids as $deviceUid) {
                    broadcast(new KDSFastFoodOrderRemoveEvent($deviceUid, $transaction, $removedItems));
                }
            }

            $kitchenDisplay->delete();

            return true;
        });
    }

    /**
     * Remove menu in kitchen display.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function removeMenu($data)
    {
        return $this->transaction(function () use ($data) {
            $kitchenDisplayDetail = KitchenDisplayDetail::find($data['kitchen_display_detail_bid']);
            $transaction = null;
            
            // Get transaction info before deletion
            $terminal = CDISTerminal::where('number', $kitchenDisplayDetail->terminal_number)->first();
            if ($terminal) {
                $transaction = CDISTerminalTransaction::where([
                    'transaction_id' => $kitchenDisplayDetail->transaction_id,
                    'terminal_bid' => $terminal->bid
                ])->first();
            }
            
            $removedItem = [
                'bid' => $kitchenDisplayDetail->product_uom_packaging_bid,
                'product_bid' => $kitchenDisplayDetail->product_uom_packaging_bid,
                'name' => $kitchenDisplayDetail->name,
                'quantity' => $kitchenDisplayDetail->remaining_quantity,
                'remaining_quantity' => $kitchenDisplayDetail->remaining_quantity,
                'transaction_id' => $kitchenDisplayDetail->transaction_id,
                'order_type_name' => $kitchenDisplayDetail->order_type_name,
                'order_type_id' => $kitchenDisplayDetail->order_type_id ?? '',
                'status' => $kitchenDisplayDetail->status,
            ];
            
            $kitchenDisplayDetail->update([
                'status' => MenuStatus::DELETED
            ]);
            $kitchenDisplayDetail->delete();
            
            // Dispatch remove menu event
            if ($transaction) {
                $deviceUid = $this->getDeviceUidForItem($kitchenDisplayDetail->product_uom_packaging_bid, $kitchenDisplayDetail->kitchen_station_index);
                if ($deviceUid) {
                    broadcast(new KDSFastFoodMenuRemoveEvent($deviceUid, $removedItem));
                }
            }

            return true;
        });
    }

    /**
     * Get the device UID for a product at a specific station index.
     */
    protected function getDeviceUidForItem($productBid, $stationIndex)
    {
        $kitchenSetup = app()->make(KitchenItemSetupRepository::class)->getKitchenStation($productBid, $stationIndex);
        return $kitchenSetup['device_uid'] ?? null;
    }

    /**
     * Get all unique device UIDs affected by a set of items.
     */
    protected function getAffectedDeviceUids(array $items): array
    {
        $deviceUids = [];
        foreach ($items as $item) {
            $item = (array) $item;
            $productBid = $item['product_bid'] ?? $item['bid'] ?? '';
            $stationIndex = $item['kitchen_station_index'] ?? 1;
            $deviceUid = $this->getDeviceUidForItem($productBid, $stationIndex);
            if ($deviceUid && !in_array($deviceUid, $deviceUids)) {
                $deviceUids[] = $deviceUid;
            }
        }
        return $deviceUids;
    }
}
