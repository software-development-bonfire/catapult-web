<?php

namespace App\Services;

use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Enums\KDS\MenuStatus;
use App\Repositories\Contracts\POS\TerminalTransactionRepository;
use App\Traits\DatabaseTransaction;
use Carbon\Carbon;

class KitchenDisplayService
{
    use DatabaseTransaction;

    public function storeMenu($data) {
        return $this->transaction(function () use($data){
            foreach ($data as $datum) {
                $datum = (object) $datum;
        

                $headData = [
                    'terminal_bid' => $datum->terminal_bid,
                    'date' => $datum->date,
                    'transaction_id' => $datum->transaction_id,
                    'transaction_type' => $datum->transaction_type,
                    'log_date' => $datum->log_date,
                    
                    //'bid' => $datum->bid,
                    'amount' => $datum->amount,
                    'is_zread' => $datum->is_zread,
                    'type' => $datum->type,
                    'status' => $datum->status,
                    'gross' => $datum->gross,
                    'total_quantity' => $datum->total_quantity,
                    'total_free_items_amount' => $datum->total_free_items_amount,
                    'total_tax_amount' => $datum->total_tax_amount,
                    'total_local_tax_amount' => $datum->total_local_tax_amount,
                    'total_discount_amount' => $datum->total_discount_amount,
                    'total_vat_deduct_amount' => $datum->total_vat_deduct_amount,
                    'total_vat_exempt_amount' => $datum->total_vat_exempt_amount,
                    'total_vatable_sales' => $datum->total_vatable_sales,
                    'total_zero_rated_sales' => $datum->total_zero_rated_sales,
                    'order_number' => $datum->order_number,
                    'table_number' => $datum->table_number,
                    'guest_count' => $datum->guest_count,
                ];

                $headData['created_by'] = $datum->created_by;
                $headData['updated_by'] = $datum->updated_by;
                $headData['created_at'] = $datum->created_at;
                $headData['updated_at'] = $datum->updated_at;
                $headData['deleted_at'] = $datum->deleted_at;

                $terminalTransaction = app()->make(TerminalTransactionRepository::class)->where($headData);


                foreach ($terminalTransaction->details() as $detail) {
                    $detail = (object) $detail;

                    KitchenDisplay::create([
                        'transaction_detail_bid' => $detail->bid,
                    ]);

                 
                }
            }

        });
    }

    public function storeOrder($data) {
        return $this->transaction(function () use($data){

        });
    }

    /**
     * Move menu to other station.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function moveMenu($data)
    {
        return $this->transaction(function () use($data) {
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
        return $this->transaction(function () use($data) {
            $kitchenDisplay = KitchenDisplay::find($data['kitchen_display_bid']);

            $kitchenDisplay->details()->each(function($detail) {
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
        return $this->transaction(function () use($data) {
            $kitchenDisplayDetail = KitchenDisplayDetail::find($data['kitchen_display_detail_bid']);
            $kitchenDisplayDetail->update([
                'status' => MenuStatus::DELETED
            ]);
            $kitchenDisplayDetail->delete();

            return true;
        });
    }
}


