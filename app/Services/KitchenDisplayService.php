<?php

namespace App\Services;

use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Enums\KDS\MenuStatus;
use App\Traits\DatabaseTransaction;

class KitchenDisplayService
{
    use DatabaseTransaction;

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
            $headBid = $kitchenDisplayDetail->head_bid;
            $transactionProductBid = $kitchenDisplayDetail->transaction_product_bid;
            $remainingQuantity = $kitchenDisplayDetail->remaining_quantity;
            $remainingQuantity = $remainingQuantity - $data['quantity'];

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
                $expectedDestinationData['status'] = MenuStatus::ON_PROCESS;
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


