<?php

namespace App\Services\POS;

use App\Entities\POSPayment;
use App\Entities\POSTerminalTransaction;
use App\Entities\POSTerminalTransactionProduct;
use App\Enums\Status;
use App\Traits\DatabaseTransaction;

class TerminalTransactionService
{
    use DatabaseTransaction;

    /**
     * Update the specified resource in storage.
     *
     * @param array  $data
     * @return mixed
     */
    public function store($deviceCode, $data)
    {
       // return $this->transaction(function () use ($data) {
            $data = (object) stringToJson($data);

            $transactionData = [
                'device_code' => $deviceCode,
                'branch_bid' => $data->branch_bid,
                'terminal_bid' => $data->terminal_bid,
                'transaction_id' => $data->transaction_id,
                'log_date' => $data->log_date,
                'or_number' => $data->or_number,
                'split_number' => $data->split_number,
                'is_first_transaction' => $data->is_first_transaction,
                'type' => $data->type,
                'status' => $data->status,
                'gross_sales' => $data->gross_sales,
                'net_sales' => $data->net_sales,
                'total_quantity' => $data->total_quantity,
                'total_free_items_amount' => $data->total_free_items_amount,
                'total_local_tax_amount' => $data->total_local_tax_amount,
                'total_tax_amount' => $data->total_tax_amount,
                'total_discount_amount' => $data->total_discount_amount,
                'total_vat_deduct_amount' => $data->total_vat_deduct_amount,
                'total_vat_exempt_amount' => $data->total_vat_exempt_amount,
                'total_vatable_sales' => $data->total_vatable_sales,
                'total_zero_rated_sales' => $data->total_zero_rated_sales,
                'total_tender' => $data->total_tender,
                'eligible_amount_to_earn_points' => $data->eligible_amount_to_earn_points,
                'guest_count' => $data->guest_count,
                'service_charge' => $data->service_charge,
                'order_number' => $data->order_number,
                'table_number' => $data->table_number,
                'customer_type' => $data->customer_type,
                'customer_bid' => $data->customer_bid,
                'customer_name' => $data->customer_name,
                'customer_address' => $data->customer_address,
                'cashier_bid' => $data->cashier_bid,
                'cashier_name' => $data->cashier_name,
                'remarks' => $data->remarks,
                'created_at' => $data->created_at,
                'updated_at' => $data->updated_at,
                'change' => $data->change,
                'payment' => $data->payment,
                'is_reset' => $data->is_reset,
                'receipt' => $data->receipt,
            ];

            $posTransaction = POSTerminalTransaction::where('terminal_bid', '=', $data->terminal_bid)
                ->where('log_date', $data->log_date)
                ->where('transaction_id', $data->transaction_id)
                ->where('or_number', $data->or_number)
                ->where('order_number', $data->order_number)
                ->where('order_status', Status::INACTIVE)
                ->first();

            if ($posTransaction) {
                $posTransaction = tap($posTransaction)->update($transactionData);
            } else {
                $posTransaction = POSTerminalTransaction::create($transactionData);
            }

            if (isset($data->details) && count($data->details) > 0) {
                $this->storeDetail($data->details);
            }
            if (isset($data->payments) && count($data->payments) > 0) {
                $this->storePayment($data->payments);
            }

            return $posTransaction;
       // });
    }

    public function storeDetail($details)
    {
        //return $this->transaction(function () use ($details) {
            foreach ($details as $data) {
                $data = (object) $data;
                $transactionData = [
                    'cart_bid' => $data->cart_bid,
                    'terminal_transaction_bid' => $data->terminal_transaction_bid,
                    'usage_type' => $data->usage_type,
                    'product_bid' => $data->product_bid,
                    'name' => $data->name,
                    'description' => $data->description,
                    'long_description' => $data->long_description,
                    'menu_code' => $data->menu_code,
                    'category_bid' => $data->category_bid,
                    'quantity' => $data->quantity,
                    'tax_percentage' => $data->tax_percentage,
                    'order_type_id' => $data->order_type_id,
                    'order_type_name' => $data->order_type_name,
                    'is_free' => $data->is_free,
                    'tax_code' => $data->tax_code,
                    'original_price' => $data->original_price,
                    'price' => $data->price,
                    'individual_total_amount' => $data->individual_total_amount,
                    'individual_total_discount' => $data->individual_total_discount,
                    'entire_discount' => $data->entire_discount,
                    'entire_amount' => $data->entire_amount,
                    'vatable_sales' => $data->vatable_sales,
                    'zero_rated_sales' => $data->zero_rated_sales,
                    'tax' => $data->tax,
                    'vat_deduct' => $data->vat_deduct,
                    'vat_exempt' => $data->vat_exempt,
                    'remarks' => $data->remarks,
                    'supervisor_bid' => $data->supervisor_bid,
                    'supervisor_name' => $data->supervisor_name,
                    'created_at' => $data->created_at,
                    'parent_id' => $data->parent_id,
                    'add_on' => $data->add_on,
                    'take_home' => $data->take_home,
                    'sub_total' => $data->sub_total,
                    'gross_total' => $data->gross_total,
                    'net_total' => $data->net_total,
                    'transaction_date' => $data->transaction_date,
                    'log_date' => $data->log_date,
                    'cashier_bid' => $data->cashier_bid,
                    'cashier_name' => $data->cashier_name,
                    'discount_bid' => $data->discount_bid,
                    'discount_value' => $data->discount_value,
                    'is_reset' => $data->is_reset,
                ];
                $posTransactionProduct = POSTerminalTransactionProduct::where('cart_bid', $data->cart_bid)
                    ->where('log_date', $data->log_date)
                    ->where('terminal_transaction_bid', $data->terminal_transaction_bid)
                    ->where('product_bid', $data->product_bid)
                    ->first();

                if ($posTransactionProduct) {

                    $posTransactionProduct = tap($posTransactionProduct)->update($transactionData);
                } else {
                    $posTransactionProduct = POSTerminalTransactionProduct::create($transactionData);
                }
            }
            return $posTransactionProduct;
        //});
    }

    public function storePayment($payments)
    {
        //return $this->transaction(function () use ($payments) {
            foreach ($payments as $data) {
                $data = (object) $data;
                $paymentData = [
                    'terminal_transaction_bid' => $data->terminal_transaction_bid,
                    'payment_method_bid' => $data->payment_method_bid,
                    'title' => $data->title,
                    'amount' => $data->amount,
                    'status' => $data->status,
                    'created_at' => $data->created_at,
                    'log_date' => $data->log_date,
                    'account_number' => $data->account_number,
                    'remarks' => $data->remarks,
                ];
                $posPayment = POSPayment::where('payment_method_bid', $data->payment_method_bid)
                    ->where('log_date', $data->log_date)
                    ->where('terminal_transaction_bid', $data->terminal_transaction_bid)
                    ->first();

                if ($posPayment) {
                    $posPayment = tap($posPayment)->update($paymentData);
                } else {
                    $posPayment = POSPayment::create($paymentData);
                }
            }
            return $posPayment;
       // });
    }

    public function updateStatus($data)
    {
        return $this->transaction(function () use ($data) {
            $data = (object) stringToJson($data);
            $posTransaction = POSTerminalTransaction::where('terminal_bid', '=', $data->terminal_bid)
                ->where('log_date', $data->log_date)
                ->where('transaction_id', $data->transaction_id)
                ->where('or_number', $data->or_number)
                ->where('order_number', $data->order_number)
                ->where('order_status', Status::INACTIVE)
                ->first();

            if ($posTransaction) {
                $posTransaction = tap($posTransaction)->update([
                    'order_status' => Status::ACTIVE,
                ]);
            }
            return $posTransaction;
        });
    }
}
