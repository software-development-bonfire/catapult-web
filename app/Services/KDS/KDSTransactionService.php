<?php

namespace App\Services\KDS;

use App\Entities\CDISProductUomPackaging;
use App\Entities\CDISTerminal;
use App\Entities\CDISTerminalTransaction;
use App\Enums\UsageType;
use App\Repositories\Contracts\KitchenItemSetupRepository;
use App\Traits\KitchenDisplayTrait;
use App\Traits\QueryHelper;

class KDSTransactionService
{
    use KitchenDisplayTrait;
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
        $terminalTransaction = $data instanceof CDISTerminalTransaction
            ? $data
            : (object) $data;

        if (!$terminalTransaction || !isset($terminalTransaction->terminal_bid)) {
            return;
        }

        $terminal = CDISTerminal::find($terminalTransaction->terminal_bid);

        if (!$terminal) {
            return;
        }

        // Eager load all relationships in one query instead of N+1 individual lookups
        if ($terminalTransaction instanceof CDISTerminalTransaction) {
            $terminalTransaction->load('details.products.addons');
        }

        // Build KDS transaction header directly from the stored model
        $kdsTransaction = clone $terminalTransaction;
        $kdsTransaction['terminal_number'] = $terminal->number;
        $kdsTransaction['transaction_type'] = $terminalTransaction->transaction_type;
        $kdsTransaction['kitchen_station_index'] = 1;
        $kdsTransaction['table_number'] = $terminalTransaction->table_number ?? '';
        $kdsTransaction['queue_number'] = $terminalTransaction->queue_number ?? '';

        $flattenProducts = [];
        $flattenIndex = 0;

        $this->disableForeignKeyChecks();

        // Iterate through stored relationships directly
        foreach ($terminalTransaction->details as $transactionDetail) {
            foreach ($transactionDetail->products as $storedProduct) {

                // Collect addon names for display from loaded addons
                $addonNames = [];
                foreach ($storedProduct->addons as $addon) {
                    $prefix = '';
                    if ($addon->usage_type == UsageType::ADDON) {
                        $prefix = '(A) ';
                    } else if ($addon->usage_type == UsageType::BUNDLE) {
                        $prefix = '(MOD) ';
                    }
                    $addonNames[] = $prefix . $addon->name;
                }

                // Build flatten product detail from stored product data
                $productDetail = [
                    'index' => $flattenIndex,
                    'bid' => $storedProduct->product_bid,
                    'product_bid' => $storedProduct->product_bid,
                    'name' => $storedProduct->name,
                    'menu_code' => $storedProduct->menu_code,
                    'description' => $storedProduct->description,
                    'long_description' => $storedProduct->long_description,
                    'quantity' => $storedProduct->quantity,
                    'remaining_quantity' => $storedProduct->quantity,
                    'usage_type' => '',
                    'special_request' => $storedProduct->special_request ?? '',
                    'is_addon' => false,
                    'transaction_id' => $terminalTransaction->transaction_id,
                    'transaction_product_bid' => $storedProduct->bid,
                    'order_type_id' => $storedProduct->order_type_id,
                    'order_type_name' => $storedProduct->order_type_name,
                    'terminal_number' => $terminal->number,
                    'addons' => empty($addonNames) ? '' : implode(', ', $addonNames),
                    'has_addon' => $storedProduct->addons->count() > 0,
                    'kitchen_station_index' => 1,
                    'table_number' => $terminalTransaction->table_number ?? '',
                    'queue_number' => $terminalTransaction->queue_number ?? '',
                    'max_prep_time' => $this->getMaxPrepTime($storedProduct->product_bid),
                ];
                $flattenProducts[] = $productDetail;

                // Persist to KitchenDisplay/KitchenDisplayDetail tables (products without addons)
                if ($storedProduct->addons->isEmpty()) {
                    $this->validateKitchenDisplay($transactionDetail, $storedProduct, $productDetail);
                }

                // Process addons from loaded relationship
                foreach ($storedProduct->addons as $storedAddon) {
                    $flattenIndex += 1;

                    $addonDetail = [
                        'index' => $flattenIndex,
                        'bid' => $storedAddon->product_bid,
                        'product_bid' => $storedAddon->product_bid,
                        'name' => $storedAddon->name,
                        'menu_code' => $storedAddon->menu_code,
                        'description' => $storedAddon->description,
                        'long_description' => $storedAddon->long_description,
                        'quantity' => $storedAddon->quantity,
                        'remaining_quantity' => $storedProduct->quantity,
                        'usage_type' => $storedAddon->usage_type,
                        'special_request' => $storedAddon->special_request ?? '',
                        'is_addon' => true,
                        'transaction_id' => $terminalTransaction->transaction_id,
                        'transaction_product_bid' => $storedAddon->bid,
                        'order_type_id' => $storedAddon->order_type_id,
                        'order_type_name' => $storedAddon->order_type_name,
                        'terminal_number' => $terminal->number,
                        'addons' => $storedProduct->name,
                        'has_addon' => false,
                        'kitchen_station_index' => 1,
                        'table_number' => $terminalTransaction->table_number ?? '',
                        'queue_number' => $terminalTransaction->queue_number ?? '',
                        'max_prep_time' => $this->getMaxPrepTime($storedAddon->product_bid),
                    ];
                    $flattenProducts[] = $addonDetail;

                    // Persist to KitchenDisplay/KitchenDisplayDetail tables for addon
                    $this->validateKitchenDisplay($transactionDetail, $storedAddon, $addonDetail);
                }

                $flattenIndex += 1;
            }
        }

        $this->enableForeignKeyChecks();

        $result = [
            'kds_transaction' => $kdsTransaction,
            'flatten_products' => $flattenProducts,
        ];
        return $result;
    }

    /**
     * Get max preparation time for a product from its UOM packaging configuration.
     */
    private function getMaxPrepTime($productBid): float
    {
        $packaging = CDISProductUomPackaging::where('bid', $productBid)->first();
        return $packaging ? (float) $packaging->max_prep_time : 0;
    }
}
