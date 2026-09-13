<?php

namespace App\Services\KDS;

use App\Enums\UsageType;
use App\Traits\KitchenDisplayTrait;
use App\Traits\ProductImagePathTrait;
use App\Traits\QueryHelper;
use App\Entities\StoreOrder;
use App\Entities\StoreOrderDetail;
use App\Entities\CDISProductUomPackaging;
use Illuminate\Support\Facades\Log;

class StoreOrderService
{
    use KitchenDisplayTrait;
    use ProductImagePathTrait;
    use QueryHelper;

    /**
     * Construct KDS Order data and persist to KDS tables (KitchenDisplay/KitchenDisplayDetail).
     * Returns kds_transaction metadata and flatten_products for broadcasting.
     *
     */
    public function storeCreateUpdate($data)
    {
        $transactions = [];
        foreach ($data as $datum) {
            $datum = (object) $datum;

            $primaryHeadData = [
                'terminal_bid' => $datum->terminal_bid,
                'terminal_id' => $datum->terminal_id,
                'transaction_id' => $datum->transaction_id,
                'transaction_type' => $datum->transaction_type,
                'order_number' => $datum->order_number,
                'log_date' => $datum->log_date,
            ];

            $secondaryData = [
                'transaction_from' => $datum->transaction_from ?? 1,
                'table_id' => $datum->table_number,
            ];


            $storeOrder = StoreOrder::updateOrCreate(
                $primaryHeadData,
                $secondaryData
            );
            $isAdditionalTransaction = false;
            if (!$storeOrder->wasRecentlyCreated) {
                $isAdditionalTransaction = true;
            }

            $storeOrderDetail = $this->storeDetail($datum->product, $storeOrder, $isAdditionalTransaction);

            // $storeOrder = app()->make(StoreOrder::class)
            //     ->where($primaryHeadData);

            // if ($storeOrder->count() > 0 ?? false) {
            //     // $this->deleteRelatedDiscounts($terminalTransaction);
            //     $storeOrder->delete();
            // }
            $storeOrder['products'] = $storeOrderDetail;
            $transactions = $storeOrder;
        }

        return $transactions;
    }

    public function storeDetail($products, $storeOrder, $isAdditionalTransaction = false)
    {
        $orderDetail = [];
        foreach ($products as $product) {
            $product = (object) $product;

            $productUomViaMenuCode = CDISProductUomPackaging::where([
                'barcode' => $product->menu_code,
            ])->first();

            if ($productUomViaMenuCode) {
                $primaryHeadData = [
                    'store_order_bid' => $storeOrder->bid,
                    'product_uom_bid' => $productUomViaMenuCode->bid,
                    'is_additional' => $product->is_additional,
                    'is_removed' => $product->is_removed,
                ];

                $secondaryData = [
                    'menu_code' => $product->menu_code,
                    'name' => $product->name,
                    'description' => $product->description,
                    'long_description' => $product->long_description,
                    'quantity' => $product->quantity,
                    'usage_type' => UsageType::PRODUCT,
                    'is_addon' => $product->is_addon ?? 0,
                    'order_type_id' => $product->order_type_id,
                    'special_request' => $product->special_request,
                ];

                $storeOrderDetail = StoreOrderDetail::updateOrCreate(
                    $primaryHeadData,
                    $secondaryData
                );

                
                $storeOrderDetail->product_bid = $storeOrderDetail->product_uom_bid;
                $orderDetail[] = $storeOrderDetail;
            }
            
        }

        return $orderDetail;
        
    }
}
