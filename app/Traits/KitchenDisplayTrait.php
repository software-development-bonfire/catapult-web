<?php

namespace App\Traits;

use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Enums\KDS\MenuStatus;
use Illuminate\Support\Carbon;

/**
 * Trait KitchenDisplayTrait
 * @package App\Traits
 */
trait KitchenDisplayTrait
{
    private function buildKitchenDisplay($transactionDetailBid, $data)
    {
        $kitchenDisplay = KitchenDisplay::create([
            'transaction_detail_bid' => $transactionDetailBid,
        ]);
        if ($kitchenDisplay) {
            KitchenDisplayDetail::create([
                'head_bid' => $kitchenDisplay->bid,
                'transaction_product_bid' => $data['transaction_product_bid'],
                'remaining_quantity' => $data['remaining_quantity'],
                'kitchen_station_bid' => $data['kitchen_station_bid'],
                'status' => MenuStatus::ON_PROCESS,
            ]);
        }
    }
}
