<?php

namespace App\Services\KDS;

use App\Entities\CDISBranch;
use App\Entities\CDISKitchenItemSetupDetail;
use App\Entities\CDISProductUomPackaging;
use App\Entities\StoreOrder;
use App\Entities\CDISTerminal;
use App\Enums\UsageType;
use App\Repositories\Contracts\KitchenItemSetupRepository;
use App\Traits\KitchenDisplayTrait;
use App\Traits\ProductImagePathTrait;
use App\Traits\QueryHelper;
use Illuminate\Support\Facades\Log;

class KDSTransactionV2Service
{
    use KitchenDisplayTrait;
    use ProductImagePathTrait;
    use QueryHelper;

    /**
     * Construct KDS transaction data and persist to KDS tables (KitchenDisplay/KitchenDisplayDetail).
     * Returns kds_transaction metadata and flatten_products for broadcasting.
     *
     * Accepts the already-stored CDISTerminalTransaction model from TerminalTransactionService.
     * No redundant re-queries needed since data is already persisted.
     */
    public function store($data)
    {

        $storeOrder = $data instanceof StoreOrder
            ? $data
            : (object) $data;
        // if (!$terminalTransaction || !isset($terminalTransaction->terminal_bid)) {
        //     return;
        // }

        $terminal = CDISTerminal::find($storeOrder->terminal_bid);
        $transactionDetail = [];
        // if (!$terminal) {
        //     return;
        // }

        // // Eager load all relationships in one query instead of N+1 individual lookups
        // if ($terminalTransaction instanceof StoreOrder) {
        //     $terminalTransaction->load('details.products.addons');
        // }

        // // Get customer/cashier info from the first detail record
        // // $firstDetail = $terminalTransaction instanceof CDISTerminalTransaction
        // //     ? $terminalTransaction->details->first()
        // //     : null;

        // // Build KDS transaction header as plain array for deterministic broadcast serialization
        $kdsTransaction = [
            'bid' => $storeOrder->bid,
            'terminal_bid' => $storeOrder->terminal_bid ?? $storeOrder->terminal_id,
            'terminal_number' => $terminal->number ?? 001,
            'transaction_id' => $storeOrder->transaction_id,
            'date' => $storeOrder->log_date,
            'amount' => $storeOrder->amount,
            'transaction_type' => $storeOrder->transaction_type,
            'kitchen_station_index' => 1,
            'log_date' => $storeOrder->log_date,
            'order_number' => $storeOrder->order_number ?? '',
            'table_number' => $storeOrder->table_number ?? '',
            'queue_number' => $storeOrder->queue_number ?? '',
            'guest_count' => $storeOrder->guest_count ?? '1',
            'remarks' => $storeOrder->remarks ?? '',
            'customer_type' => $firstDetail->customer_type ?? '',
            'customer_bid' => $firstDetail->customer_bid ?? '',
            'customer_name' => $firstDetail->customer_name ?? '',
            'customer_address' => $firstDetail->customer_address ?? '',
            'cashier_bid' => $firstDetail->cashier_bid ?? '',
            'cashier_name' => $firstDetail->cashier_name ?? '',
            'sent_at' => parseSqlDateTime($storeOrder->sent_at) ?? parseSqlDateTime($storeOrder->created_at),
            'created_at' => parseSqlDateTime($storeOrder->created_at),
            'updated_at' => parseSqlDateTime($storeOrder->updated_at),
            'is_additional' => $storeOrder->is_additional
        ];

        $flattenProducts = [];
        $flattenIndex = 0;

        // $this->disableForeignKeyChecks();
        // $isAdditionalTransaction = $terminalTransaction->is_additional;
        // // Iterate through stored relationships directly
            foreach ($storeOrder->products as $storedProduct) {

                //FILTER IF ADDITIONAL TRANSACTION AND ADDITIONAL ORDER OR NOT
                //  if ( ($isAdditionalTransaction && $storedProduct->is_additional) || (! $isAdditionalTransaction) ) { 
                // Collect addon names for display from loaded addons
                $addonNames = [];
                // foreach ($storedProduct->addons as $addon) {
                //     $prefix = '';
                //     if ($addon->usage_type == UsageType::ADDON) {
                //         $prefix = '(A) ';
                //     } else if ($addon->usage_type == UsageType::BUNDLE) {
                //         $prefix = '(MOD) ';
                //     }
                //     $addonNames[] = $prefix . $addon->name;
                // }
                $maxColumnTime = $this->getMaxColumnTime($storedProduct->product_uom_bid);
                // Build flatten product detail from stored product data
                
                    $productDetail = [
                        'index' => $flattenIndex,
                        'bid' => $storedProduct->product_uom_bid,
                        'product_bid' => $storedProduct->product_uom_bid,
                        'name' => $storedProduct->name,
                        'menu_code' => $storedProduct->menu_code,
                        'description' => $storedProduct->description,
                        'pos_description' => 'POS-'.$storedProduct->description,
                        'short_description' => $storedProduct->description,
                        'long_description' => $storedProduct->long_description,
                        'menu_description' => preg_replace('/[^a-zA-Z0-9]|[aeiouAEIOU]/', '', $storedProduct->description),
                        'quantity' => $storedProduct->quantity,
                        'is_additional' => $storedProduct->is_additional,
                        'remaining_quantity' => $storedProduct->quantity,
                        'usage_type' => '',
                        'special_request' => $storedProduct->special_request ?? '',
                        'is_addon' => false,
                        'transaction_id' => $storeOrder->transaction_id,
                        'transaction_type' => $storeOrder->transaction_type, // This help to indetify the transaction type (VOID ORDER, VOID ITEM)for the product in the kitchen display detail table
                        'transaction_product_bid' => $storedProduct->bid,
                        'order_type_id' => $storedProduct->order_type_id,
                        'order_type_name' => $storedProduct->order_type_name,
                        'terminal_number' => $terminal->number,
                        'addons' => empty($addonNames) ? '' : implode(', ', $addonNames),
                        // 'has_addon' => $storedProduct->addons->count() > 0,
                        'has_addon' => 0,
                        'kitchen_station_index' => 1,
                        'table_number' => $storeOrder->table_number ?? '',
                        'queue_number' => $storeOrder->queue_number ?? '',
                        'max_waiting_time' => $maxColumnTime['max_waiting_time'],
                        'max_prep_time' => $maxColumnTime['max_prep_time'],
                        'max_assembling_time' => $maxColumnTime['max_assembly_time'],
                        'max_serving_time' => $maxColumnTime['max_serving_time'],
                        'sent_at' => parseSqlDateTime($storedProduct->sent_at) ?? parseSqlDateTime($storedProduct->created_at),
                        'created_at' => parseSqlDateTime($storedProduct->created_at),
                        'updated_at' => parseSqlDateTime($storedProduct->updated_at),
                    ];
                
                

                     // Persist to KitchenDisplay/KitchenDisplayDetail tables (products without addons)
                    if (true) {
                    // if ($storedProduct->addons->isEmpty()) {
                        $kitchenDisplay = $this->validateKitchenDisplay($storeOrder, $transactionDetail, $storedProduct, $productDetail);

                        $productDetail['kitchen_display_bid'] = $kitchenDisplay ? $kitchenDisplay['kitchen_display_id'] : null;
                        $productDetail['kitchen_display_detail_bid'] = $kitchenDisplay ? $kitchenDisplay['kitchen_display_detail_id'] : null;
                        $productDetail['kitchen_transaction_detail_bid'] = $kitchenDisplay ? $kitchenDisplay['kitchen_transaction_detail_bid'] : null;
                    }
    
                    $productDetail['presentation_url'] = $this->getProductImagePath($storedProduct->product_uom_bid);
                    $productDetail['recipe_url'] = $this->getProductRecipeUrl($storedProduct->product_uom_bid);

                    $flattenProducts[] = $productDetail;
                    // Process addons from loaded relationship
                    // foreach ($storedProduct->addons as $storedAddon) {
                    //     $flattenIndex += 1;
                    //     $maxColumnTime = $this->getMaxColumnTime($storedAddon->product_bid);
                    //     $addonDetail = [
                    //         'index' => $flattenIndex,
                    //         'bid' => $storedAddon->product_bid,
                    //         'product_bid' => $storedAddon->product_bid,
                    //         'name' => $storedAddon->name,
                    //         'menu_code' => $storedAddon->menu_code,
                    //         'description' => $storedAddon->description,
                    //         'pos_description' => 'POS-'.$storedAddon->description,
                    //         'short_description' => $storedAddon->description,
                    //         'long_description' => $storedAddon->long_description,
                    //         'menu_description' => preg_replace('/[^a-zA-Z0-9]|[aeiouAEIOU]/', '', $storedAddon->description),
                    //         'quantity' => $storedAddon->quantity,
                    //         'remaining_quantity' => $storedAddon->quantity,
                    //         'usage_type' => $storedAddon->usage_type,
                    //         'special_request' => $storedAddon->special_request ?? '',
                    //         'is_addon' => true,
                    //         'transaction_id' => $terminalTransaction->transaction_id,
                    //         'transaction_type' => $terminalTransaction->transaction_type, // This help to indetify the transaction type (VOID ORDER, VOID ITEM)for the product in the kitchen display detail table
                    //         'transaction_product_bid' => $storedAddon->bid,
                    //         'order_type_id' => $storedAddon->order_type_id,
                    //         'order_type_name' => $storedAddon->order_type_name,
                    //         'terminal_number' => $terminal->number,
                    //         'addons' => $storedAddon->name,
                    //         'has_addon' => false,
                    //         'kitchen_station_index' => 1,
                    //         'table_number' => $terminalTransaction->table_number ?? '',
                    //         'queue_number' => $terminalTransaction->queue_number ?? '',
                    //         'max_waiting_time' => $maxColumnTime['max_waiting_time'],
                    //         'max_prep_time' => $maxColumnTime['max_prep_time'],
                    //         // 'max_assembly_time' => $maxColumnTime['max_assembly_time'],
                    //         'max_serving_time' => $maxColumnTime['max_serving_time'],
                    //         'sent_at' => parseSqlDateTime($storedAddon->sent_at),
                    //         'created_at' => parseSqlDateTime($storedAddon->created_at),
                    //         'updated_at' => parseSqlDateTime($storedAddon->updated_at),
                    //     ];

                    //     // Persist to KitchenDisplay/KitchenDisplayDetail tables for addon
                    //     $kitchenDisplay = $this->validateKitchenDisplay($terminalTransaction, $transactionDetail, $storedAddon, $addonDetail);
                    //     $addonDetail['kitchen_display_bid'] = $kitchenDisplay ? $kitchenDisplay['kitchen_display_id'] : null;
                    //     $addonDetail['kitchen_display_detail_bid'] = $kitchenDisplay ? $kitchenDisplay['kitchen_display_detail_id'] : null;
                    //     $addonDetail['kitchen_transaction_detail_bid'] = $kitchenDisplay ? $kitchenDisplay['kitchen_transaction_detail_bid'] : null;

                    //     $addonDetail['presentation_url'] = $this->getProductImagePath($storedAddon->product_bid);
                    //     $addonDetail['recipe_url'] = $this->getProductRecipeUrl($storedAddon->product_bid);
                        
                    //     $flattenProducts[] = $addonDetail;
                    // }
                // }

                $flattenIndex += 1;
            }
        // }

        // $this->enableForeignKeyChecks();

        $result = [
            'kds_transaction' => $kdsTransaction,
            'flatten_products' => $flattenProducts,
        ];
        return $result;
    }

    /**
     * Get max preparation time for a product from kitchen item setup detail,
     * filtered by the current branch configuration.
     */
    private function getMaxColumnTime($productBid): array
    {
        $branchBid = CDISBranch::where('code', config('configuration.branch_code'))
            ->whereNull('deleted_at')
            ->value('bid');

        if (!$branchBid) {
            return [
                "max_waiting_time" => 0,
                "max_prep_time" => 0,
                "max_assembly_time" => 0,
                "max_serving_time" => 0
            ];
        }

        $detail = CDISKitchenItemSetupDetail::where('product_uom_packaging_bid', $productBid)
            ->whereHas('kitchenItemSetup', function ($query) use ($branchBid) {
                $query->where('branch_bid', $branchBid)->whereNull('deleted_at');
            })
            ->whereNull('deleted_at')
            ->first();

        if ($detail) {
            $data = [
                "max_waiting_time" => (float) $detail->max_waiting_time ?? 0,
                "max_prep_time" => (float) $detail->max_prep_time ?? 0,
                "max_assembly_time" => (float) $detail->max_assembly_time ?? 0,
                "max_serving_time" => (float) $detail->max_serving_time ?? 0
            ];

            return $data;
        }

        $packaging = CDISProductUomPackaging::where('bid', $productBid)->first();

        if ($packaging) {
            $data = [
                "max_waiting_time" => (float) $packaging->max_waiting_time ?? 0,
                "max_prep_time" => (float) $packaging->max_prep_time ?? 0,
                "max_assembly_time" => (float) $packaging->max_assembly_time ?? 0,
                "max_serving_time" => (float) $packaging->max_serving_time ?? 0
            ];

            return $data;
        }
    }
}
