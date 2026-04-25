<?php

namespace App\Services\StationOTS;

use App\Entities\StationOTSTerminalTransaction;
use App\Entities\StationOTSTerminalTransactionProduct;
use App\Events\TransactionEvent;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    public function store($data)
    {
        $data = (object) $data;

        $transaction = StationOTSTerminalTransaction::create([
            'branch_bid' => $data->branch_bid ?? null,
            'device_code' => $data->device_code ?? null,
            'terminal_bid' => $data->terminal_bid ?? null,
            'transaction_id' => $data->transaction_id ?? null,
            'log_date' => $data->log_date ?? null,
            'or_number' => $data->or_number ?? null,
            'split_number' => $data->split_number ?? null,
            'is_first_transaction' => $data->is_first_transaction ?? null,
            'type' => $data->type ?? null,
            'device_type' => $data->device_type ?? null,
            'device_mode' => $data->device_mode ?? null,
            'device_mode_label' => $data->device_mode_label ?? null,
            'status' => $data->status ?? null,
            'gross_sales' => $data->gross_sales ?? 0,
            'net_sales' => $data->net_sales ?? 0,
            'total_quantity' => $data->total_quantity ?? 0,
            'total_free_items_amount' => $data->total_free_items_amount ?? 0,
            'total_local_tax_amount' => $data->total_local_tax_amount ?? 0,
            'total_tax_amount' => $data->total_tax_amount ?? 0,
            'total_discount_amount' => $data->total_discount_amount ?? 0,
            'total_delivery_fee' => $data->total_delivery_fee ?? 0,
            'total_vat_deduct_amount' => $data->total_vat_deduct_amount ?? 0,
            'total_vat_exempt_amount' => $data->total_vat_exempt_amount ?? 0,
            'total_vatable_sales' => $data->total_vatable_sales ?? 0,
            'total_zero_rated_sales' => $data->total_zero_rated_sales ?? 0,
            'total_tender' => $data->total_tender ?? 0,
            'eligible_amount_to_earn_points' => $data->eligible_amount_to_earn_points ?? 0,
            'guest_count' => $data->guest_count ?? 0,
            'service_charge' => $data->service_charge ?? 0,
            'order_number' => $data->order_number ?? null,
            'locator_number' => $data->locator_number ?? null,
            'order_type' => $data->order_type ?? null,
            'order_schedule' => $data->order_schedule ?? null,
            'billing_type' => $data->billing_type ?? null,
            'table_id' => $data->table_id ?? null,
            'table_number' => $data->table_number ?? null,
            'customer_type' => $data->customer_type ?? null,
            'customer_bid' => $data->customer_bid ?? null,
            'customer_name' => $data->customer_name ?? null,
            'customer_address' => $data->customer_address ?? null,
            'cashier_bid' => $data->cashier_bid ?? null,
            'cashier_name' => $data->cashier_name ?? null,
            'remarks' => $data->remarks ?? null,
            'change' => $data->change ?? 0,
            'payment' => $data->payment ?? 0,
            'payment_status' => $data->payment_status ?? null,
            'is_reset' => $data->is_reset ?? 0,
            'receipt' => $data->receipt ?? null,
        ]);

        if (!empty($data->details) && is_array($data->details)) {
            foreach ($data->details as $product) {
                $product = (object) $product;

                $transaction->details()->create([
                    'bid' => $product->bid ?? null,
                    'cart_bid' => $product->cart_bid ?? null,
                    'terminal_transaction_bid' => $transaction->bid,
                    'usage_type' => $product->usage_type ?? null,
                    'product_type' => $product->product_type ?? 0,
                    'product_bid' => $product->product_bid ?? null,
                    'parent_bid' => $product->parent_bid ?? null,
                    'name' => $product->name ?? null,
                    'description' => $product->description ?? null,
                    'long_description' => $product->long_description ?? null,
                    'menu_code' => $product->menu_code ?? null,
                    'category_bid' => $product->category_bid ?? null,
                    'quantity' => $product->quantity ?? 0,
                    'tax_percentage' => $product->tax_percentage ?? 0,
                    'order_type_id' => $product->order_type_id ?? null,
                    'order_type_name' => $product->order_type_name ?? null,
                    'is_free' => $product->is_free ?? 0,
                    'tax_code' => $product->tax_code ?? null,
                    'original_price' => $product->original_price ?? 0,
                    'price' => $product->price ?? 0,
                    'individual_total_amount' => $product->individual_total_amount ?? 0,
                    'individual_total_discount' => $product->individual_total_discount ?? 0,
                    'total_addon_amount' => $product->total_addon_amount ?? 0,
                    'entire_discount' => $product->entire_discount ?? 0,
                    'entire_amount' => $product->entire_amount ?? 0,
                    'vatable_sales' => $product->vatable_sales ?? 0,
                    'zero_rated_sales' => $product->zero_rated_sales ?? 0,
                    'tax' => $product->tax ?? 0,
                    'vat_deduct' => $product->vat_deduct ?? 0,
                    'vat_exempt' => $product->vat_exempt ?? 0,
                    'remarks' => $product->remarks ?? null,
                    'special_request' => $product->special_request ?? null,
                    'supervisor_bid' => $product->supervisor_bid ?? null,
                    'supervisor_name' => $product->supervisor_name ?? null,
                    'parent_id' => $product->parent_id ?? null,
                    'add_on' => $product->add_on ?? null,
                    'take_home' => $product->take_home ?? null,
                    'sub_total' => $product->sub_total ?? 0,
                    'gross_total' => $product->gross_total ?? 0,
                    'net_total' => $product->net_total ?? 0,
                    'transaction_date' => $product->transaction_date ?? null,
                    'log_date' => $product->log_date ?? null,
                    'cashier_bid' => $product->cashier_bid ?? null,
                    'cashier_name' => $product->cashier_name ?? null,
                    'discount_bid' => $product->discount_bid ?? null,
                    'discount_value' => $product->discount_value ?? null,
                    'status' => $product->status ?? 0,
                    'is_reset' => $product->is_reset ?? 0,
                ]);
            }
        }

        $transaction->load('details');

        return $transaction;
    }
}
