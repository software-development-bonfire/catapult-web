<?php

namespace App\Services;

use App\Entities\CDISProductUomPackaging;
use App\Entities\CDISTerminal;
use App\Entities\CDISTerminalTransaction;
use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Enums\KDS\MenuStatus;
use App\Events\KDSTransactionEvent;
use App\Events\MyPrivateEvent;
use App\Repositories\Contracts\KitchenItemSetupRepository;
use App\Repositories\Contracts\POS\TerminalTransactionRepository;
use App\Traits\DatabaseTransaction;
use Carbon\Carbon;

class KitchenDisplayService
{
    use DatabaseTransaction;


    /**
     * Move menu to other station.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function doneOrder($data)
    {

        $data = (object) stringToJson($data);
        $transaction = (object) $data->transaction;
        $terminalTransaction = CDISTerminalTransaction::where([
            'transaction_id' => $transaction->transaction_id,
            'terminal_bid' => $transaction->terminal_bid
        ])->with('details')->first();

        if (!$terminalTransaction) {
            return;
        }

        $details = $terminalTransaction->details;
        foreach ($details as $detail) {
            $kitchenDisplays = KitchenDisplay::where('transaction_detail_bid', $detail->bid)->get();
            if ($kitchenDisplays->isNotEmpty()) {
                foreach ($kitchenDisplays as $kitchenDisplay) {
                    // Get all kitchen details with specific kitchen station number/index

                    $kitchenDisplayDetails = KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)
                        ->selectRaw('*, SUM(remaining_quantity) as remaining_quantity')
                        ->groupBy('head_bid', 'transaction_product_bid', 'product_uom_packaging_bid')
                        ->get();

                    if ($kitchenDisplayDetails->isNotEmpty()) {
                        $releasingDetails = [];
                        foreach ($kitchenDisplayDetails as $kitchenDisplayDetail) {
                            $releasingDetails[] = [
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
                        }

                        // Grouped by order type name, then assigned items by order type susch DINE IN, TAKE OUT, DRIVE THRU, etc.
                        $groupedReleasingDisplays = collect($releasingDetails)->groupBy('order_type_name');
                        foreach ($groupedReleasingDisplays->toArray() as $orderType => $items) {
                            if (! empty($orderType) && count($items) > 0) {
                                // Broadcast to assigned KDS for Releasing
                                broadcast(new KDSTransactionEvent('', $transaction, $items, $orderType, 'update'));
                            }
                        }
                    }
                }
            }
            // If releasing KDS sent request to make this transaction DONE
            // Broadcast all KDS that contains transaction
            broadcast(new KDSTransactionEvent('', $transaction, [], '', 'done'));
        }


        return $data;
    }

    /**
     * Move menu to other station.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function moveOrder($data)
    {

        $data = (object) stringToJson($data);
        $transaction = (object) $data->transaction;
        $terminalTransaction = CDISTerminalTransaction::where([
            'transaction_id' => $transaction->transaction_id,
            'terminal_bid' => $transaction->terminal_bid
        ])->with('details')->first();

        if (!$terminalTransaction) {
            \Illuminate\Support\Facades\Log::alert('No terminal transaction found');
            return;
        }

        $details = $terminalTransaction->details;
        foreach ($details as $detail) {
            $kitchenDisplays = KitchenDisplay::where('transaction_detail_bid', $detail->bid)->get();
            if ($kitchenDisplays->isNotEmpty()) {
                foreach ($kitchenDisplays as $kitchenDisplay) {
                    // Get all kitchen details with specific kitchen station number/index
                    $this->getKitchenDetailsWithStationIndex($transaction, $kitchenDisplay, $kitchenDisplay->bid, $transaction->kitchen_station_index);
                }
            }
        }


        return $data;
    }


    private function getKitchenDetailsWithStationIndex($transaction, $kitchenDisplay, $kitchenDisplayBid, $index)
    {
        // Get all kitchen details with specific kitchen station number/index
        $kitchenDisplayDetails = KitchenDisplayDetail::where([
            'head_bid' => $kitchenDisplayBid,
            'kitchen_station_index' => $index,
            'status' => MenuStatus::ON_PROCESS,
        ])->get();
        if ($kitchenDisplayDetails->isNotEmpty()) {
            $releasingDetails = [];
            $nextStationDetails = [];

            // Update all remaining QTY to zero to current index,
            foreach ($kitchenDisplayDetails as $kitchenDisplayDetail) {
                $kitchenDisplayDetail = (object) $kitchenDisplayDetail;
                // Check if next sttion is present then updates the quantity
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
                    broadcast(new MyPrivateEvent($device, $transaction, $items, ''));
                }
            }
            // Grouped by order type name, then assigned items by order type susch DINE IN, TAKE OUT, DRIVE THRU, etc.
            $groupedReleasingDisplays = collect($releasingDetails)->groupBy('order_type_name');
            foreach ($groupedReleasingDisplays->toArray() as $orderType => $items) {
                if (! empty($orderType) && count($items) > 0) {
                    // Broadcast to assigned KDS for Releasing
                    broadcast(new KDSTransactionEvent('', $transaction, $items, $orderType, 'update'));
                }
            }
        } else {
            // Grouped by order type name, then assigned items by order type susch DINE IN, TAKE OUT, DRIVE THRU, etc.
            $groupedReleasingDisplays = collect($kitchenDisplayDetails)->groupBy('order_type_name');
            foreach ($groupedReleasingDisplays->toArray() as $orderType => $items) {
                if (! empty($orderType) && count($items) > 0) {
                    // Broadcast to assigned KDS for Releasing
                    //broadcast(new KDSTransactionEvent('', $transaction, $items, $orderType, 'update'));
                }
            }
        }
    }


    /**
     * Move menu to other station.
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
                $kitchenDisplayDetail = (object) $kitchenDisplayDetail;
                // Check if next sttion is present then updates the quantity
                $kitchenDisplayDetails2 = KitchenDisplayDetail::where([
                    'head_bid' => $kitchenDisplayDetail->head_bid,
                    'transaction_product_bid' => $kitchenDisplayDetail->transaction_product_bid,
                    'product_uom_packaging_bid' => $kitchenDisplayDetail->product_uom_packaging_bid,
                    'kitchen_station_index' => intval($item->kitchen_station_index) + 1
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

                }
                // Update remaining quantity to 0, because it moves to next stations
                $kitchenDisplayDetail->update(['remaining_quantity' => 0, 'status' => $kitchenOrderStatus]);
            }

            $terminal = CDISTerminal::where('number', $item->terminal_number)->first();
            $terminalTransaction = CDISTerminalTransaction::where([
                'transaction_id' => $item->transaction_id,
                'terminal_bid' => $terminal->bid
            ])->with('details')->first();
            $transaction = clone $terminalTransaction;
            $transaction['terminal_number'] = $item->terminal_number;
            $transaction['kitchen_station_index'] = intval($item->kitchen_station_index) + 1;
            // Get configured Kitchen Display of each products
            $groupedDisplays = collect($nextStationDetails)->groupBy('device_uid');
            foreach ($groupedDisplays->toArray() as $device => $items) {
                if (! empty($device) && count($items) > 0) {
                    // Broadcast to assigned KDS

                    broadcast(new MyPrivateEvent($device, $transaction, $items, ''));
                }
            }
            // Grouped by order type name, then assigned items by order type susch DINE IN, TAKE OUT, DRIVE THRU, etc.
            $groupedReleasingDisplays = collect($releasingDetails)->groupBy('order_type_name');
            foreach ($groupedReleasingDisplays->toArray() as $orderType => $items) {
                if (! empty($orderType) && count($items) > 0) {
                    // Broadcast to assigned KDS for Releasing
                    broadcast(new KDSTransactionEvent('', $transaction, $items, $orderType, 'update'));
                }
            }
        } else {
            

        }
        return $data;
    }

    /**
     * Move menu to other station.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function moveItem($data)
    {
        return $this->transaction(function () use ($data) {
            $kitchenDisplayDetail = KitchenDisplayDetail::find($data['kitchen_display_detail_bid']);

            if ($kitchenDisplayDetail->status == MenuStatus::DONE) {
                return false;
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

            $kitchenDisplay->details()->each(function ($detail) {
                $detail->update([
                    'status' => MenuStatus::DELETED
                ]);

                $detail->delete();
            });

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
            $kitchenDisplayDetail->update([
                'status' => MenuStatus::DELETED
            ]);
            $kitchenDisplayDetail->delete();

            return true;
        });
    }
}
