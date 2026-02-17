<?php

namespace App\Traits;

use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Enums\KDS\MenuStatus;
use App\Repositories\Contracts\KitchenItemSetupRepository;
use Illuminate\Support\Carbon;

/**
 * Trait KitchenDisplayTrait
 * @package App\Traits
 */
trait KitchenDisplayTrait
{
    private function validateKitchenDisplay($transactionDetail, $transactionDetailProduct, $product)
    {
        $product = (object) $product;
        // Get information from Ktichen Item Setup (data from CDIS)
        /*
        $kitchenItemSetups = app()->make(KitchenItemSetupRepository::class)->details((object)[
            'transaction_product_bid' =>  $transactionDetailProduct->product_bid,
        ]);

        // If present Kitchen ITem Setup configuration then we need to 
        // build kitchen display initial state, get the latest configured Kitchen Display
        if ($kitchenItemSetups && count($kitchenItemSetups) > 0) {
            foreach ($kitchenItemSetups as $kitchenItemSetup) {
                $this->buildKitchenDisplay($transactionDetail->bid, [
                    'transaction_product_bid' => $transactionDetailProduct->bid,
                    'remaining_quantity' => $transactionDetailProduct->quantity,
                    'product_uom_packaging_bid' => $transactionDetailProduct->product_bid,
                    'kitchen_station_bid' => $kitchenItemSetup['kitchen_station_process_bid'],
                    'usage_type' => $transactionDetailProduct->usage_type,
                ]);
            }
        }
            */
/*
        for ($index = 0; $index <= 4; $index++) {
            $kitchenItemSetup = $index == 0 ? [] : app()->make(KitchenItemSetupRepository::class)->getKitchenStation($transactionDetailProduct->product_bid, $index);
            if ($kitchenItemSetup || $index == 0) {
                $this->buildKitchenDisplay($transactionDetail->bid, [
                    'transaction_product_bid' => $transactionDetailProduct->bid,
                    'product_uom_packaging_bid' => $transactionDetailProduct->product_bid,
                    'remaining_quantity' => ($index == 0) ? 0 : $transactionDetailProduct->quantity,
                    'kitchen_station_bid' => ($index == 0) ? 0 : $kitchenItemSetup['station_bid_' . $index],
                    'kitchen_station_index' => $index,
                    'usage_type' => $transactionDetailProduct->usage_type,
                    'order_type_name' => $transactionDetailProduct->order_type_name,
                    'name' => $transactionDetailProduct->name,
                    'special_request' => $product->special_request,
                    'is_addon' => $product->is_addon,
                    'transaction_id' => $product->transaction_id,
                    'order_type_name' => $product->order_type_name,
                    'terminal_number' => $product->terminal_number,
                    'addons' => $product->addons,
                    'kitchen_station_index' => $index,
                    'status' => $index == 0 ? MenuStatus::RELEASING : MenuStatus::ON_PROCESS,
                ]);
            }
        }
            */

        
        for ($index = 1; $index <= 4; $index++) {
            $kitchenItemSetup = app()->make(KitchenItemSetupRepository::class)->getKitchenStation($transactionDetailProduct->product_bid, $index);
            if ($kitchenItemSetup) {
                $this->buildKitchenDisplay($transactionDetail->bid, [
                    'transaction_product_bid' => $transactionDetailProduct->bid,
                    'product_uom_packaging_bid' => $transactionDetailProduct->product_bid,
                    'remaining_quantity' => ($index != 1) ? 0 : $transactionDetailProduct->quantity,
                    'kitchen_station_bid' => $kitchenItemSetup['station_bid_' . $index],
                    'kitchen_station_index' => $index,
                    'usage_type' => $transactionDetailProduct->usage_type,
                    'order_type_id' => $product->order_type_id ?? '',
                    'order_type_name' => $product->order_type_name,
                    'name' => $transactionDetailProduct->name,
                    'special_request' => $product->special_request,
                    'is_addon' => $product->is_addon,
                    'transaction_id' => $product->transaction_id,
                    'terminal_number' => $product->terminal_number,
                    'addons' => $product->addons,
                    'kitchen_station_index' => $index,
                    'status' => MenuStatus::ON_PROCESS,
                ]);
            }
        }

    }

    private function buildKitchenDisplay($transactionDetailBid, $data)
    {
        $kitchenDisplay = KitchenDisplay::where('transaction_detail_bid', '=', $transactionDetailBid)->first();
        if (! $kitchenDisplay) {
            $kitchenDisplay = KitchenDisplay::create([
                'transaction_detail_bid' => $transactionDetailBid,
            ]);
        }
        if ($kitchenDisplay) {
            $kitchenDisplayDetail = KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)
                ->where('transaction_product_bid',  $data['transaction_product_bid'])
                ->where('product_uom_packaging_bid',  $data['product_uom_packaging_bid'])
                ->where('kitchen_station_bid',  $data['kitchen_station_bid'])
                ->where('kitchen_station_index',  $data['kitchen_station_index'])
                ->where('usage_type',  $data['usage_type'])
                ->where('order_type_name',  $data['order_type_name'])
                ->first();

            if (! $kitchenDisplayDetail) {
                KitchenDisplayDetail::create([
                    'head_bid' => $kitchenDisplay->bid,
                    'transaction_product_bid' => $data['transaction_product_bid'],
                    'remaining_quantity' => $data['remaining_quantity'],
                    'kitchen_station_bid' => $data['kitchen_station_bid'],
                    'kitchen_station_index' => $data['kitchen_station_index'],
                    'product_uom_packaging_bid' => $data['product_uom_packaging_bid'],
                    'status' => $data['status'],
                    'usage_type' => $data['usage_type'],
                    'order_type_id' => $data['order_type_id'] ?? '',
                    'order_type_name' => $data['order_type_name'],
                    'name' => $data['name'],
                    'special_request' => $data['special_request'],
                    'is_addon' => $data['is_addon'],
                    'transaction_id' => $data['transaction_id'],
                    'terminal_number' => $data['terminal_number'],
                    'addons' => $data['addons'],
                    'kitchen_station_index' => $data['kitchen_station_index']
                ]);
            }
        }
        return [
            'kitchen_display_id' => $kitchenDisplay->bid,
            'kitchen_transaction_detail_bid' => $transactionDetailBid,
        ];
    }
}
