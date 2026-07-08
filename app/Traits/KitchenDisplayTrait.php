<?php

namespace App\Traits;

use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Enums\KDS\MenuStatus;
use App\Repositories\Contracts\KitchenItemSetupRepository;

/**
 * Trait KitchenDisplayTrait
 *
 * Responsible for persisting KitchenDisplay (head) and KitchenDisplayDetail records
 * when a new transaction arrives from POS. Products are assigned to their configured
 * kitchen station based on KitchenItemSetup.
 *
 * Station assignment: Items are assigned to their first configured station (station_bid).
 * Movement between stations is handled by KitchenDisplayMovementService.
 */
trait KitchenDisplayTrait
{
    /**
     * Persist KitchenDisplay records for a product/addon.
     *
     * @param object $terminalTransaction  CDISTerminalTransaction model
     * @param object $transactionDetail    CDISTerminalTransactionDetail model
     * @param object $transactionDetailProduct  CDISTerminalTransactionProduct model
     * @param array  $product  Flattened product detail array from KDSTransactionService
     * @return array|null
     */
    private function validateKitchenDisplay($terminalTransaction, $transactionDetail, $transactionDetailProduct, $product)
    {
        $product = (object) $product;

        // Resolve the first configured station for this product
        $setup = app()->make(KitchenItemSetupRepository::class)
            ->getKitchenStation($transactionDetailProduct->product_bid, 1);

        // No station configured for this product, skip
        if (!$setup) {
            return null;
        }

        $kitchenStationBid = $setup['station_bid_1'] ?? null;

        // Get or create the KitchenDisplay head record
        $kitchenDisplay = $this->getOrCreateKitchenDisplay($terminalTransaction, $transactionDetail, $product);

        if (!$kitchenDisplay) {
            return null;
        }

        // Create detail record at the first station
        $kitchenDisplayDetail = $this->createKitchenDisplayDetail($kitchenDisplay, $terminalTransaction, $transactionDetailProduct, $product, $kitchenStationBid);

        // Update total_quantity on head
        $totalQty = KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->sum('remaining_quantity');
        $kitchenDisplay->update(['total_quantity' => $totalQty]);

        return [
            'kitchen_display_id' => $kitchenDisplay->bid,
            'kitchen_transaction_detail_bid' => $transactionDetail->bid,
            'kitchen_display_detail_id' => $kitchenDisplayDetail->bid,
        ];
    }

    /**
     * Get or create the KitchenDisplay head record for a transaction detail.
     *
     * @param object $terminalTransaction
     * @param object $transactionDetail
     * @param object $product
     * @return KitchenDisplay|null
     */
    private function getOrCreateKitchenDisplay($terminalTransaction, $transactionDetail, $product)
    {
        $kitchenDisplay = KitchenDisplay::where('transaction_detail_bid', $transactionDetail->bid)->first();

        if (!$kitchenDisplay) {
            $kitchenDisplay = KitchenDisplay::create([
                'transaction_detail_bid' => $transactionDetail->bid,
                'transaction_date' => $terminalTransaction->transaction_date ?? now()->toDateString(),
                'transaction_id' => $product->transaction_id,
                'terminal_bid' => $terminalTransaction->terminal_bid,
                'terminal_number' => $product->terminal_number,
                'total_quantity' => 0,
                'completed_quantity' => 0,
            ]);
        }

        return $kitchenDisplay;
    }

    /**
     * Create a KitchenDisplayDetail record if it doesn't already exist.
     *
     * @param KitchenDisplay $kitchenDisplay
     * @param object $terminalTransaction
     * @param object $transactionDetailProduct
     * @param object $product
     * @param string|null $kitchenStationBid
     * @return KitchenDisplayDetail|null
     */
    private function createKitchenDisplayDetail($kitchenDisplay, $terminalTransaction, $transactionDetailProduct, $product, $kitchenStationBid)
    {
        // Check for duplicate: same head + product + station
        $exists = KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)
            ->where('transaction_product_bid', $transactionDetailProduct->bid)
            ->where('product_uom_packaging_bid', $transactionDetailProduct->product_bid)
            ->where('kitchen_station_bid', $kitchenStationBid)
            ->first();

        if ($exists) {
            return $exists;
        }

        return KitchenDisplayDetail::create([
            'head_bid' => $kitchenDisplay->bid,
            'transaction_product_bid' => $transactionDetailProduct->bid,
            'product_uom_packaging_bid' => $transactionDetailProduct->product_bid,
            'transaction_id' => $product->transaction_id,
            'transaction_type' => $terminalTransaction->transaction_type,
            'remaining_quantity' => $transactionDetailProduct->quantity,
            'kitchen_station_bid' => $kitchenStationBid,
            'status' => MenuStatus::WAITING,
            'order_type_id' => $product->order_type_id ?? null,
            'order_type_name' => $product->order_type_name ?? null,
            'usage_type' => $transactionDetailProduct->usage_type ?? '',
            'special_request' => $product->special_request ?? '',
            'addons' => $product->addons ?? '',
            'is_addon' => $product->is_addon ?? false,
            'max_preparation_time' => $product->max_prep_time ?? 60,
            'max_waiting_time' => $product->max_waiting_time ?? 60,
            'max_serving_time' => $product->max_serving_time ?? 60,
            'name' => $transactionDetailProduct->name,
            'terminal_number' => $product->terminal_number,
            'started_at' => now(),
        ]);
    }
}
