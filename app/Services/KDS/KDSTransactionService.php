<?php

namespace App\Services\KDS;

use App\Entities\CDISTerminal;
use App\Entities\CDISTerminalTransaction;
use App\Enums\UsageType;
use App\Repositories\Contracts\CDIS\TerminalTransactionRepository;
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
     */
    public function store($data)
    {
        $result = [];

        foreach ($data as $datum) {
            $datum = (object) $datum;

            $terminal = CDISTerminal::find($datum->terminal_bid);

            // Retrieve the stored transaction to get the DB record
            $terminalTransaction = app()->make(TerminalTransactionRepository::class)
                ->where([
                    'terminal_bid' => $datum->terminal_bid,
                    'date' => $datum->date,
                    'transaction_id' => $datum->transaction_id,
                    'transaction_type' => $datum->transaction_type,
                    'log_date' => $datum->log_date,
                ])->first();

            if (!$terminalTransaction) {
                continue;
            }

            // Build KDS transaction header
            $kdsTransaction = clone $terminalTransaction;
            $kdsTransaction['terminal_number'] = $terminal->number;
            $kdsTransaction['transaction_type'] = $datum->transaction_type;
            $kdsTransaction['kitchen_station_index'] = 1;
            $kdsTransaction['table_number'] = $datum->table_number ?? '';
            $kdsTransaction['queue_number'] = $datum->queue_number ?? '';

            $flattenProducts = [];
            $flattenIndex = 0;

            foreach ($datum->official_receipt as $officialReceipt) {
                $officialReceipt = (object) $officialReceipt;

                // Find the stored detail record
                $transactionDetail = $terminalTransaction->details()
                    ->where('transaction_head_bid', $officialReceipt->transaction_head_bid)
                    ->where('or_number', $officialReceipt->number)
                    ->first();

                if (!$transactionDetail) {
                    continue;
                }

                foreach ($officialReceipt->product as $key => $product) {
                    $product = (object) $product;

                    // Find the stored product record
                    $storedProduct = $transactionDetail->products()
                        ->where('product_bid', $product->id)
                        ->where('transaction_detail_bid', $product->transaction_detail_bid)
                        ->first();

                    if (!$storedProduct) {
                        continue;
                    }

                    // Collect addon names for display
                    $addonNames = [];
                    if (!empty($product->addon)) {
                        foreach ($product->addon as $addon) {
                            $addon = (object) $addon;
                            $prefix = '';
                            if ($addon->usage_type == UsageType::ADDON) {
                                $prefix = '(A) ';
                            } else if ($addon->usage_type == UsageType::BUNDLE) {
                                $prefix = '(MOD) ';
                            }
                            $addonNames[] = $prefix . $addon->name;
                        }
                    }

                    // Build flatten product detail for main product
                    $productDetail = [
                        'index' => $flattenIndex,
                        'bid' => $product->bid,
                        'product_bid' => $product->product_bid,
                        'name' => $product->name,
                        'menu_code' => $product->menu_code,
                        'description' => $product->description,
                        'long_description' => $product->long_description,
                        'quantity' => $product->quantity,
                        'remaining_quantity' => $product->quantity,
                        'usage_type' => '',
                        'special_request' => $product->special_request ?? '',
                        'is_addon' => false,
                        'transaction_id' => $terminalTransaction->transaction_id,
                        'transaction_product_bid' => $storedProduct->bid,
                        'order_type_id' => $product->order_type_id,
                        'order_type_name' => $product->order_type_name,
                        'terminal_number' => $terminal->number,
                        'addons' => empty($addonNames) ? '' : implode(', ', $addonNames),
                        'has_addon' => count($product->addon) > 0,
                        'kitchen_station_index' => 1,
                        'table_number' => $datum->table_number ?? '',
                        'queue_number' => $datum->queue_number ?? '',
                    ];
                    $flattenProducts[] = $productDetail;

                    // Persist to KitchenDisplay/KitchenDisplayDetail tables (products without addons)
                    if (count($product->addon) <= 0) {
                        $this->validateKitchenDisplay($transactionDetail, $storedProduct, $productDetail);
                    }

                    // Process addons
                    foreach ($product->addon as $addon) {
                        $addon = (object) $addon;

                        $storedAddon = $storedProduct->addons()
                            ->where('bid', $addon->bid)
                            ->first();

                        if (!$storedAddon) {
                            $flattenIndex += 1;
                            continue;
                        }

                        $flattenIndex += 1;

                        $addonDetail = [
                            'index' => $flattenIndex,
                            'bid' => $addon->bid,
                            'product_bid' => $addon->product_bid,
                            'name' => $addon->name,
                            'menu_code' => $addon->menu_code,
                            'description' => $addon->description,
                            'long_description' => $addon->long_description,
                            'quantity' => $addon->quantity,
                            'remaining_quantity' => $product->quantity,
                            'usage_type' => $addon->usage_type,
                            'special_request' => $addon->special_request ?? '',
                            'is_addon' => true,
                            'transaction_id' => $terminalTransaction->transaction_id,
                            'transaction_product_bid' => $storedAddon->bid,
                            'order_type_id' => $addon->order_type_id,
                            'order_type_name' => $addon->order_type_name,
                            'terminal_number' => $terminal->number,
                            'addons' => $product->name,
                            'has_addon' => false,
                            'kitchen_station_index' => 1,
                            'table_number' => $datum->table_number ?? '',
                            'queue_number' => $datum->queue_number ?? '',
                        ];
                        $flattenProducts[] = $addonDetail;

                        // Persist to KitchenDisplay/KitchenDisplayDetail tables for addon
                        $this->disableForeignKeyChecks();
                        $this->validateKitchenDisplay($transactionDetail, $storedAddon, $addonDetail);
                        $this->enableForeignKeyChecks();
                    }

                    $flattenIndex += 1;
                }
            }

            $result = [
                'kds_transaction' => $kdsTransaction,
                'flatten_products' => $flattenProducts,
            ];
        }

        return $result;
    }
}
