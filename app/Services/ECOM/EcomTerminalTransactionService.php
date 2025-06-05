<?php

namespace App\Services\ECOM;

use App\Entities\POSPayment;
use App\Entities\POSTerminalTransaction;
use App\Entities\POSTerminalTransactionProduct;
use App\Enums\Status;
use App\Enums\API\deviceType;
use App\Enums\KDS\OrderType;
use App\Traits\DatabaseTransaction;

class EcomTerminalTransactionService
{
    use DatabaseTransaction;

    /**
     * Update the specified resource in storage.
     *
     * @param array  $data
     * @return mixed
     */
    public function store($data, $deviceType = DeviceType::ECOMMERCE)
    {
       // return $this->transaction(function () use ($data) {
            $transaction = $data->order_information->values;
            $orderType = $transaction->order_type ?? OrderType::DELIVERY;
            $detail = $data->cart;
            $transactionData = [
                'device_code' => $transaction->device_code,
                'branch_bid' => $transaction->branch_bid,
                'terminal_bid' => 999,
                'transaction_id' => 0,
                'log_date' => date('Y-m-d H:i:s'),
                'or_number' => 0,
                'split_number' => 0,
                'is_first_transaction' => 0,
                'type' => 0,
                'device_type' => $deviceType,
                'status' => Status::ACTIVE,
                'gross_sales' => $transaction->sub_total,
                'net_sales' => $transaction->sub_total,
                'total_quantity' => $transaction->total_quantity,
                'total_free_items_amount' => 0,
                'total_local_tax_amount' => 0,
                'total_tax_amount' => 0,
                'total_discount_amount' => 0,
                'total_vat_deduct_amount' => 0,
                'total_vat_exempt_amount' => 0,
                'total_vatable_sales' => 0,
                'total_zero_rated_sales' => 0,
                'total_tender' => 0,
                'eligible_amount_to_earn_points' => 0,
                'guest_count' => 0,
                'service_charge' => 0,
                'order_number' => $transaction->order_number,
                'order_type' => $orderType,
                'order_schedule' => $transaction->order_schedule ?? null,
                'table_number' => 0,
                'customer_type' => null,
                'customer_bid' => null,
                'customer_name' => null,
                'customer_address' => null,
                'cashier_bid' => null,
                'cashier_name' => null,
                'remarks' => null,
                'change' => 0,
                'payment' => 0,
                'is_reset' => 0,
                'receipt' => 0,
            ];

            $posTransaction = POSTerminalTransaction::create($transactionData);


            if (isset($detail) && count($detail)) {
                $this->storeDetail($detail, $posTransaction->bid, $orderType);
            }
            
            return $posTransaction;
       // });
    }

    public function storeDetail($details, $terminalTransactionBid, $orderType = OrderType::DELIVERY)
    {
        //return $this->transaction(function () use ($details) {
            foreach ($details as $data) {
                $data = (object) $data;
                $transactionData = [
                    'cart_bid' => 0,
                    'terminal_transaction_bid' => $terminalTransactionBid,
                    'usage_type' => 0,
                    'product_bid' => $data->bid,
                    'name' => $data->name,
                    'description' => $data->description,
                    'long_description' => $data->long_description,
                    'menu_code' => $data->barcode ?? '-',
                    'category_bid' => $data->category_bid,
                    'quantity' => (integer) $data->qty,
                    'tax_percentage' => 0,
                    'order_type_id' => $orderType,
                    'order_type_name' => OrderType::getDescription($orderType),
                    'is_free' => 0,
                    'tax_code' => 0,
                    'original_price' => $data->price,
                    'price' => $data->price,
                    'individual_total_amount' => $data->price,
                    'individual_total_discount' => 0,
                    'entire_discount' => 0,
                    'entire_amount' => 0,
                    'vatable_sales' => 0,
                    'zero_rated_sales' => 0,
                    'tax' => 0,
                    'vat_deduct' => 0,
                    'vat_exempt' => 0,
                    'parent_id' => 0,
                    'add_on' => 0,
                    'take_home' => 0,
                    'sub_total' => $data->price,
                    'gross_total' => $data->price,
                    'net_total' => $data->price,
                    'discount_value' => 0,
                    'is_reset' => 0,
                ];
               
                $posTransactionProduct = POSTerminalTransactionProduct::create($transactionData);

                $this->storeModifier($data->modifiers, $terminalTransactionBid, $orderType = OrderType::DELIVERY);

            }
            return $posTransactionProduct;
        //});
    }

    public function storeModifier($details, $terminalTransactionBid, $orderType = OrderType::DELIVERY)
    {
        foreach ($details as $data) {
                $data = (object) $data;
                $transactionData = [
                    'cart_bid' => 0,
                    'terminal_transaction_bid' => $terminalTransactionBid,
                    'usage_type' => $data->usage,
                    'product_bid' => $data->bid,
                    'name' => $data->description,
                    'description' => $data->description,
                    'long_description' => $data->long_description,
                    'menu_code' => $data->item_code ?? '-',
                    'category_bid' => $data->category_bid ?? 1,
                    'quantity' => (integer) $data->quantity,
                    'tax_percentage' => 0,
                    'order_type_id' => $orderType,
                    'order_type_name' => OrderType::getDescription($orderType),
                    'is_free' => 0,
                    'tax_code' => 0,
                    'original_price' => $data->price,
                    'price' => $data->price,
                    'individual_total_amount' => $data->price,
                    'individual_total_discount' => 0,
                    'entire_discount' => 0,
                    'entire_amount' => 0,
                    'vatable_sales' => 0,
                    'zero_rated_sales' => 0,
                    'tax' => 0,
                    'vat_deduct' => 0,
                    'vat_exempt' => 0,
                    'parent_id' => 0,
                    'add_on' => 0,
                    'take_home' => 0,
                    'sub_total' => $data->price,
                    'gross_total' => $data->price,
                    'net_total' => $data->price,
                    'discount_value' => 0,
                    'is_reset' => 0,
                ];
               
                $posTransactionProduct = POSTerminalTransactionProduct::create($transactionData);

            }
    }

    public function updateOrder($data)
    {
        $data = (object) $data->transaction;
        $transaction = POSTerminalTransaction::where('order_number', $data->reference_number);

        if ($transaction) {
            $transaction->update([
                'payment_status' => $data->payment_status,
            ]);
        }

        return $transaction;
    }


}
