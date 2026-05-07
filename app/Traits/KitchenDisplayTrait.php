<?php

namespace App\Traits;

use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Enums\KDS\KDSSystemMode;
use App\Enums\KDS\MenuStatus;
use App\Enums\POS\DeviceMode;
use App\Repositories\Contracts\KitchenItemSetupRepository;

/**
 * Trait KitchenDisplayTrait
 *
 * Responsible for persisting KitchenDisplay (head) and KitchenDisplayDetail records
 * when a new transaction arrives from POS. Products are assigned to their configured
 * kitchen stations based on KitchenItemSetup.
 *
 * Station sequence: Items start at station 1 and move forward (1→2→3→...→0 releasing).
 * Only station 1 gets the initial quantity; subsequent stations start with 0 until
 * items are explicitly moved by the KDS operator.
 */
trait KitchenDisplayTrait
{
    /**
     * Persist KitchenDisplay records for a product/addon.
     *
     * @param object $transactionDetail  CDISTerminalTransactionDetail model
     * @param object $transactionDetailProduct  CDISTerminalTransactionProduct model
     * @param array  $product  Flattened product detail array from KDSTransactionService
     * @return array|null
     */
    private function validateKitchenDisplay($terminalTransaction, $transactionDetail, $transactionDetailProduct, $product)
    {
        $product = (object) $product;

        // Resolve all configured stations for this product (up to 4)
        $stationSequence = [];
        $stationSetups = [];

        for ($index = 1; $index <= 4; $index++) {
            $setup = app()->make(KitchenItemSetupRepository::class)
                ->getKitchenStation($transactionDetailProduct->product_bid, $index);

            if ($setup) {
                $stationSequence[] = $index;
                $stationSetups[$index] = $setup;
            }
        }

        // No station configured for this product, skip
        if (empty($stationSequence)) {
            return null;
        }

        // Append releasing station (index 0) at the end of the sequence
        $stationSequence[] = 0;

        // Get or create the KitchenDisplay head record
        $kitchenDisplay = $this->getOrCreateKitchenDisplay($terminalTransaction, $transactionDetail, $product);

        if (!$kitchenDisplay) {
            return null;
        }

        // Create detail record only for the first station (where item starts)
        $firstIndex = $stationSequence[0];
        $firstSetup = $stationSetups[$firstIndex];

        $this->createKitchenDisplayDetail($kitchenDisplay, $transactionDetailProduct, $product, [
            'kitchen_station_bid' => $firstSetup['station_bid_' . $firstIndex],
            'kitchen_station_index' => $firstIndex,
            'current_station_index' => $firstIndex,
            'station_sequence' => json_encode($stationSequence),
            'current_position_in_sequence' => 0,
        ]);

        return [
            'kitchen_display_id' => $kitchenDisplay->bid,
            'kitchen_transaction_detail_bid' => $transactionDetail->bid,
        ];
    }

    /**
     * Get or create the KitchenDisplay head record for a transaction detail.
     *
     * @param object $transactionDetail
     * @param object $product
     * @return KitchenDisplay|null
     */
    private function getOrCreateKitchenDisplay($terminalTransaction,$transactionDetail, $product)
    {
        $kitchenDisplay = KitchenDisplay::where('transaction_detail_bid', $transactionDetail->bid)->first();

        if (!$kitchenDisplay) {
            $isFineDine = isset($terminalTransaction['device_mode']) && ($terminalTransaction['device_mode'] !== DeviceMode::FAST_FOOD);
            $kitchenDisplay = KitchenDisplay::create([
                'transaction_detail_bid' => $transactionDetail->bid,
                'terminal_bid' => $terminalTransaction->terminal_bid,
                'transaction_id' => $product->transaction_id,
                'terminal_number' => $product->terminal_number,
                'order_type_id' => $product->order_type_id ?? '',
                'order_type_name' => $product->order_type_name ?? '',
                'system_mode' => $isFineDine ? KDSSystemMode::DB_FINE_DINE : KDSSystemMode::DB_FAST_FOOD,
                'status' => 'PREPARING',
            ]);
        }

        return $kitchenDisplay;
    }

    /**
     * Create a KitchenDisplayDetail record if it doesn't already exist.
     *
     * @param KitchenDisplay $kitchenDisplay
     * @param object $transactionDetailProduct
     * @param object $product
     * @param array $stationData
     * @return KitchenDisplayDetail|null
     */
    private function createKitchenDisplayDetail($kitchenDisplay, $transactionDetailProduct, $product, array $stationData)
    {
        // Check for duplicate: same head + product + station
        $exists = KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)
            ->where('transaction_product_bid', $transactionDetailProduct->bid)
            ->where('product_uom_packaging_bid', $transactionDetailProduct->product_bid)
            ->where('kitchen_station_bid', $stationData['kitchen_station_bid'])
            ->where('kitchen_station_index', $stationData['kitchen_station_index'])
            ->first();

        if ($exists) {
            return $exists;
        }

        return KitchenDisplayDetail::create([
            'head_bid' => $kitchenDisplay->bid,
            'transaction_product_bid' => $transactionDetailProduct->bid,
            'product_uom_packaging_bid' => $transactionDetailProduct->product_bid,
            'transaction_id' => $product->transaction_id,
            'terminal_number' => $product->terminal_number,
            'name' => $transactionDetailProduct->name,
            'original_quantity' => $transactionDetailProduct->quantity,
            'remaining_quantity' => $transactionDetailProduct->quantity,
            'kitchen_station_bid' => $stationData['kitchen_station_bid'],
            'kitchen_station_index' => $stationData['kitchen_station_index'],
            'current_station_index' => $stationData['current_station_index'],
            'station_sequence' => $stationData['station_sequence'],
            'current_position_in_sequence' => $stationData['current_position_in_sequence'],
            'usage_type' => $transactionDetailProduct->usage_type ?? '',
            'order_type_id' => $product->order_type_id ?? '',
            'order_type_name' => $product->order_type_name ?? '',
            'special_request' => $product->special_request ?? '',
            'is_addon' => $product->is_addon ?? false,
            'addons' => $product->addons ?? '',
            'status' => MenuStatus::ON_PROCESS,
        ]);
    }
}
