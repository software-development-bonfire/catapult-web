<?php

namespace App\Services\POS;

use App\Entities\CDISInventoryLocationTag;
use App\Entities\CDISTerminal;
use App\Entities\CDISTerminalTransaction;
use App\Enums\InventoryLocationTagType;
use App\Repositories\Contracts\CDIS\TerminalTransactionRepository;
use App\Repositories\Contracts\KitchenItemSetupRepository;
use Illuminate\Support\Facades\Log;
use App\Traits\DatabaseTransaction;
use App\Traits\KitchenDisplayTrait;
use App\Traits\QueryHelper;
use App\Traits\TerminalTransactionDiscountTrait;

class TerminalTransactionService
{
    use DatabaseTransaction;
    use QueryHelper;
    use TerminalTransactionDiscountTrait;
    use KitchenDisplayTrait;

    public function store($data)
    {
        //$this->transaction(function () use ($data) {
            $transactions = [];
            foreach ($data as $datum) {
                $datum = (object) $datum;

                //$branchBid = CDISTerminal::find($datum->terminal_bid)->branch->bid;

                $primaryHeadData = [
                    'terminal_bid' => $datum->terminal_bid,
                    'date' => $datum->date,
                    'transaction_id' => $datum->transaction_id,
                    'transaction_type' => $datum->transaction_type,
                    'log_date' => $datum->log_date,
                ];

                $terminalTransaction = app()->make(TerminalTransactionRepository::class)
                    ->where($primaryHeadData);

                if ($terminalTransaction->count() > 0 ?? false) {
                    $this->deleteRelatedDiscounts($terminalTransaction);
                    $terminalTransaction->forceDelete();

                    // Added to add indentifier to if terminal is already resent
                    // Perhaps it reprint or retried sending
                    // Use to validate and restrict reprinting kitchen and sticker printing
                    $transactions['is_reprint'] = true;
                }

                $secondaryHeadData = [
                    'bid' => $datum->bid,
                    'amount' => $datum->amount,
                    'is_zread' => $datum->is_zread,
                    'type' => $datum->type,
                    'status' => $datum->status,
                    'gross' => $datum->gross,
                    'total_quantity' => $datum->total_quantity,
                    'total_free_items_amount' => $datum->total_free_items_amount,
                    'total_tax_amount' => $datum->total_tax_amount,
                    'total_local_tax_amount' => $datum->total_local_tax_amount,
                    'total_discount_amount' => $datum->total_discount_amount,
                    'total_vat_deduct_amount' => $datum->total_vat_deduct_amount,
                    'total_vat_exempt_amount' => $datum->total_vat_exempt_amount,
                    'total_vatable_sales' => $datum->total_vatable_sales,
                    'total_zero_rated_sales' => $datum->total_zero_rated_sales,
                    'order_number' => $datum->order_number,
                    'table_number' => $datum->table_number,
                    'guest_count' => $datum->guest_count,
                ];

                $headData = array_merge($primaryHeadData, $secondaryHeadData);

                $headData['created_by'] = $datum->created_by;
                $headData['updated_by'] = $datum->updated_by;
                $headData['created_at'] = $datum->created_at;
                $headData['updated_at'] = $datum->updated_at;
                $headData['deleted_at'] = $datum->deleted_at;

                $terminalTransaction = CDISTerminalTransaction::create($headData);

                $transactions = $terminalTransaction;

                $official_receipt = [];

                foreach ($datum->official_receipt as $officialReceipt) {
                    $officialReceipt = (object) $officialReceipt;

                    $terminalTransactionDetail = $terminalTransaction->details()->create([
                        'transaction_head_bid' => $officialReceipt->transaction_head_bid,
                        'or_number' => $officialReceipt->number,
                        'split_number' => $officialReceipt->split_number,
                        'total' => $officialReceipt->total,
                        'discount_amount' => $officialReceipt->discount_amount,
                        'free_items_amount' => $officialReceipt->free_items_amount,
                        'vat_deduct_amount' => $officialReceipt->vat_deduct_amount,
                        'vat_exempt_amount' => $officialReceipt->vat_exempt_amount,
                        'original_amount' => $officialReceipt->original_amount,
                        'quantity' => $officialReceipt->quantity,
                        'local_tax_amount' => $officialReceipt->local_tax_amount,
                        'tax_amount' => $officialReceipt->tax_amount,
                        'service_charge' => $officialReceipt->service_charge,
                        'vatable_sales' => $officialReceipt->vatable_sales,
                        'zero_rated_sales' => $officialReceipt->zero_rated_sales,
                        'eligible_amount_to_earn_points' => $officialReceipt->eligible_amount_to_earn_points,
                        'total_tender' => $officialReceipt->total_tender,
                        'customer_type' => $officialReceipt->customer_type,
                        'customer_bid' => $officialReceipt->customer_bid,
                        'customer_name' => $officialReceipt->customer_name,
                        'customer_address' => $officialReceipt->customer_address,
                        'cashier_bid' => $officialReceipt->cashier_bid,
                        'cashier_name' => $officialReceipt->cashier_name,
                        'created_at' => $officialReceipt->created_at,
                        'updated_at' => $officialReceipt->updated_at,
                        'deleted_at' => $officialReceipt->deleted_at,
                    ]);

                    $official_receipt = $terminalTransactionDetail;

                    if (isset($officialReceipt->discount)) {
                        foreach ($officialReceipt->discount as $discount) {
                            $discount = (object) $discount;

                            $terminalTransactionDetail->discounts()->create([
                                'bid' => $discount->bid,
                                'transaction_product_bid' => $discount->transaction_product_bid,
                                'discount_bid' => $discount->discount_bid,
                                'title' => $discount->title,
                                'total' => $discount->total,
                                'amount_discount' => $discount->amount_discount,
                                'vat_deduct' => $discount->vat_deduct,
                                'mandated' => $discount->mandated,
                                'created_at' => $officialReceipt->created_at,
                                'updated_at' => $officialReceipt->updated_at,
                                'deleted_at' => $officialReceipt->deleted_at,
                            ]);
                        }
                    }

                    if (isset($officialReceipt->payment_method)) {
                        foreach ($officialReceipt->payment_method as $paymentMethod) {
                            $paymentMethod = (object) $paymentMethod;

                            $terminalTransactionDetail->paymentMethods()->create([
                                'bid' => $paymentMethod->bid,
                                'transaction_detail_bid' => $paymentMethod->transaction_detail_bid,
                                'title' => $paymentMethod->title,
                                'total' => $paymentMethod->total,
                                'account_number' => $paymentMethod->account_number,
                                'created_at' => $officialReceipt->created_at,
                                'updated_at' => $officialReceipt->updated_at,
                                'deleted_at' => $officialReceipt->deleted_at,
                            ]);
                        }
                    }

                    $products = [];
                    $flattenProducts = [];
                    $flattenIndex = 0;
                    foreach ($officialReceipt->product as $key => $product) {
                        $product = (object) $product;

                        $this->disableForeignKeyChecks();

                        $terminalTransactionDetailProduct = $terminalTransactionDetail->products()->create([
                            'transaction_detail_bid' => $product->transaction_detail_bid,
                            'product_bid' => $product->id,
                            'name' => $product->name,
                            'description' => $product->description,
                            'long_description' => $product->long_description,
                            'menu_code' => $product->menu_code,
                            'category_bid' => $product->category_bid,
                            'category_name' => $product->category_name,
                            'quantity' => $product->quantity,
                            'tax_percentage' => $product->tax_percentage,
                            'order_type_id' => $product->order_type_id,
                            'order_type_name' => $product->order_type_name,
                            'is_free' => $product->is_free,
                            'is_vatable' => $product->is_vatable,
                            'original_price' => $product->original_price,
                            'price' => $product->price,
                            'total_addon' => $product->total_addon,
                            'total_amount' => $product->total_amount,
                            'entire_discount' => $product->entire_discount,
                            'amount_discount' => $product->amount_discount,
                            'vatable_sales' => $product->vatable_sales,
                            'zero_rated_sales' => $product->zero_rated_sales,
                            'tax' => $product->tax,
                            'vat_deduct' => $product->vat_deduct,
                            'vat_exempt' => $product->vat_exempt,
                            'split_number' => $product->split_number,
                            'supervisor_bid' => $product->supervisor_bid,
                            'supervisor_name' => $product->supervisor_name,
                            'created_at' => $officialReceipt->created_at,
                            'updated_at' => $officialReceipt->updated_at,
                            'deleted_at' => $officialReceipt->deleted_at,
                        ]);
                        $productDetail = [
                            'index' => $flattenIndex,
                            'bid' => $product->bid,
                            'product_bid' => $product->product_bid,
                            'name' => $product->name,
                            'menu_code' => $product->menu_code,
                            'description' => $product->description,
                            'long_description' => $product->long_description,
                            'quantity' => $product->quantity,
                            'usage_type' => '',
                            'special_request' => $product->special_request ?? '',
                            'is_addon' => false,
                        ];
                        $flattenProducts[] = $productDetail;

                        if (isset($product->price_override_details)) {
                            $terminalTransactionDetail->priceOverride()->create([
                                'transaction_detail_bid' => $product->price_override_details['transaction_detail_bid'],
                                'transaction_product_bid' => $product->price_override_details['transaction_product_bid'],
                                'product_bid' => $product->price_override_details['product_bid'],
                                'product_name' => $product->price_override_details['product_name'],
                                'product_description' => $product->price_override_details['product_description'],
                                'product_code' => $product->price_override_details['product_code'],
                                'old_price' => $product->price_override_details['old_price'],
                                'new_price' => $product->price_override_details['new_price'],
                                'quantity' => $product->price_override_details['quantity'],
                                'approved_by' => $product->price_override_details['approved_by'],
                                'approved_date' => $product->price_override_details['approved_date'],
                            ]);
                        }

                        $addons = [];
                        foreach ($product->addon as $addon) {
                            $addon = (object) $addon;

                            $terminalTransactionAddon = $terminalTransactionDetailProduct->addons()->create([
                                'bid' => $addon->bid,
                                'transaction_detail_bid' => $addon->transaction_detail_bid,
                                'product_bid' => $addon->product_bid,
                                'name' => $addon->name,
                                'description' => $addon->description,
                                'long_description' => $addon->long_description,
                                'menu_code' => $addon->menu_code,
                                'category_bid' => $addon->category_bid,
                                'category_name' => $addon->category_name,
                                'quantity' => $addon->quantity,
                                'tax_percentage' => $addon->tax_percentage,
                                'order_type_id' => $addon->order_type_id,
                                'order_type_name' => $addon->order_type_name,
                                'is_free' => $addon->is_free,
                                'is_vatable' => $addon->is_vatable,
                                'original_price' => $addon->original_price,
                                'price' => $addon->price,
                                'total_amount' => $addon->total_amount,
                                'vatable_sales' => $addon->vatable_sales,
                                'zero_rated_sales' => $addon->zero_rated_sales,
                                'amount_discount' => $addon->amount_discount,
                                'tax' => $addon->tax,
                                'vat_deduct' => $addon->vat_deduct,
                                'vat_exempt' => $addon->vat_exempt,
                                'split_number' => $addon->split_number,
                                'remarks' => $addon->remarks,
                                'usage_type' => $addon->usage_type,
                                'supervisor_bid' => $addon->supervisor_bid,
                                'supervisor_name' => $addon->supervisor_name,
                                'created_at' => $officialReceipt->created_at,
                                'updated_at' => $officialReceipt->updated_at,
                                'deleted_at' => $officialReceipt->deleted_at,
                            ]);

                            $addons[] = $terminalTransactionAddon;

                            $flattenIndex += 1;
                            $productDetail = [
                                'index' => $flattenIndex,
                                'bid' => $addon->bid,
                                'product_bid' => $addon->product_bid,
                                'name' => $addon->name,
                                'menu_code' => $addon->menu_code,
                                'description' => $addon->description,
                                'long_description' => $addon->long_description,
                                'quantity' => $addon->quantity,
                                'usage_type' => $addon->usage_type,
                                'special_request' => $addon->special_request,
                                'is_addon' => true,
                            ];
                            $flattenProducts[] = $productDetail;

                            $this->disableForeignKeyChecks();

                            if (isset($addon->discount)) {
                                foreach ($addon->discount as $discount) {
                                    $discount = (object) $discount;

                                    $terminalTransactionDiscount = $terminalTransactionDetailProduct->discounts()->create([
                                        'bid' => $addon->bid,
                                        'transaction_product_bid' => $terminalTransactionAddon->bid,
                                        'discount_bid' => $discount->discount_bid,
                                        'title' => $discount->title,
                                        'total' => $discount->total,
                                        'amount_discount' => $discount->amount_discount,
                                        'vat_deduct' => $discount->vat_deduct,
                                        'vat_exempt' => $discount->vat_exempt,
                                        'mandated' => $discount->mandated,
                                        'usage_type' => $discount->usage_type,
                                        'created_at' => $officialReceipt->created_at,
                                        'updated_at' => $officialReceipt->updated_at,
                                        'deleted_at' => $officialReceipt->deleted_at,
                                    ]);

                                    if ($terminalTransactionDiscount) {
                                        $terminalTransactionDiscount->transaction_product_bid = $terminalTransactionAddon->bid;
                                        $terminalTransactionDiscount->save();
                                    }
                                }
                            }
                            $this->enableForeignKeyChecks();
                        }
                        $terminalTransactionDetailProduct->addons = $addons;

                        
                        $products[] = $terminalTransactionDetailProduct;
                        $flattenIndex += 1;

                        foreach ($product->discount as $discount) {
                            $discount = (object) $discount;

                            $terminalTransactionDetailProduct->discounts()->create([
                                'bid' => $discount->bid,
                                'transaction_product_bid' => $discount->transaction_product_bid,
                                'discount_bid' => $discount->discount_bid,
                                'title' => $discount->title,
                                'total' => $discount->total,
                                'amount_discount' => $discount->amount_discount,
                                'vat_deduct' => $discount->vat_deduct,
                                'vat_exempt' => $discount->vat_exempt,
                                'mandated' => $discount->mandated,
                                'usage_type' => $discount->usage_type,
                                'created_at' => $officialReceipt->created_at,
                                'updated_at' => $officialReceipt->updated_at,
                                'deleted_at' => $officialReceipt->deleted_at,
                            ]);
                        }

                        $kitchenItemSetup = app()->make(KitchenItemSetupRepository::class)->details((object)[
                            'transaction_product_bid' =>  $terminalTransactionDetailProduct->product_bid,
                        ]);

                        if ($kitchenItemSetup && isset($kitchenItemSetup[0])) {
                            $this->buildKitchenDisplay($terminalTransactionDetail->bid, [
                                'transaction_product_bid' => $terminalTransactionDetailProduct->bid,
                                'remaining_quantity' => $terminalTransactionDetailProduct->quantity,
                                'kitchen_station_bid' => $kitchenItemSetup[0]['kitchen_station_process_bid'],
                            ]);
                        }

                        $this->enableForeignKeyChecks();
                    }
                    $official_receipt['products'] = $products;
                    $official_receipt['flatten_products'] = $flattenProducts;
                }

                $transactions['official_receipt'] = $official_receipt;
            }

            return $transactions;
       // });
    }
}
