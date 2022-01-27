<?php

namespace App\Services\CDIS\v2;

use App\Entities\CDISTerminalTransaction;
use App\Repositories\Contracts\CDIS\BranchRepository;
use App\Repositories\Contracts\CDIS\TerminalTransactionRepository;
use App\Traits\DatabaseTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;

class TerminalTransactionService
{
    use DatabaseTransaction;

    public function store($data)
    {
        return $this->transaction(function() use($data) {
            foreach ($data as $headIndex => $headData) {
                foreach ($headData as $datumIndex => $datum) {
                    $datum = (object) $datum;

                    $branch = App::make(BranchRepository::class)
                        ->with(['terminals' => function($query) use($datum) {
                            $query->where('number', $datum->terminal_number);

                            return $query;
                        }])
                        ->findWhere([
                            'code' => $datum->branch_code
                        ])
                        ->first();

                    $terminal = $branch->terminals;

                    $primaryHeadData = [
                        'terminal_bid' => $terminal[0]->bid,
                        'date' => $datum->date,
                        'transaction_id' => $datum->transaction_id,
                        'transaction_type' => $datum->transaction_type,
                        'log_date' => $datum->log_date,
                    ];

                    $terminalTransaction = app()->make(TerminalTransactionRepository::class)
                        ->where($primaryHeadData);

                    if ($terminalTransaction->count() > 0 ?? false) {
                        $terminalTransaction->forceDelete();
                    }

                    $secondaryHeadData = [
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

                    $terminalTransaction = CDISTerminalTransaction::create($headData);

                    foreach ($headData as $headDatumKey => $headDatum) {
                        $data[$headIndex][$datumIndex][$headDatumKey] = $headDatum;
                    }

                    unset($data[$headIndex][$datumIndex]['terminal_number']);
                    unset($data[$headIndex][$datumIndex]['branch_code']);
                    $data[$headIndex][$datumIndex]['bid'] = $terminalTransaction->bid;
                    $data[$headIndex][$datumIndex]['created_by'] = $terminalTransaction->created_by;
                    $data[$headIndex][$datumIndex]['updated_by'] = $terminalTransaction->updated_by;
                    $data[$headIndex][$datumIndex]['created_at'] =
                        ! is_null($terminalTransaction->created_at)
                            ? Carbon::parse($terminalTransaction->created_at)->format('Y-m-d H:i:s')
                            : null;
                    $data[$headIndex][$datumIndex]['updated_at'] =
                        ! is_null($terminalTransaction->updated_at)
                            ? Carbon::parse($terminalTransaction->updated_at)->format('Y-m-d H:i:s')
                            : null;
                    $data[$headIndex][$datumIndex]['deleted_at'] =
                        ! is_null($terminalTransaction->deleted_at)
                            ? Carbon::parse($terminalTransaction->deleted_at)->format('Y-m-d H:i:s')
                            : null;

                    if (isset($datum->official_receipt)) {
                        foreach ($datum->official_receipt as $officialReceiptIndex => $officialReceipt) {
                            $officialReceipt = (object) $officialReceipt;

                            $officialReceiptData = [
                                'transaction_head_bid' => $terminalTransaction->bid,
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
                                'customer_type' => $officialReceipt->customer['type'] ?? 0,
                                'customer_bid' => $officialReceipt->customer['id'] ?? null,
                                'customer_name' => $officialReceipt->customer['name'] ?? null,
                                'customer_address' => $officialReceipt->customer['address'] ?? null,
                                'cashier_bid' => $officialReceipt->cashier['id'],
                                'cashier_name' => $officialReceipt->cashier['name'],
                            ];

                            $terminalTransactionDetail = $terminalTransaction->details()->create($officialReceiptData);

                            foreach ($officialReceiptData as $officialReceiptDatumKey => $officialReceiptDatum) {
                                $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex][$officialReceiptDatumKey] = $officialReceiptDatum;
                            }

                            unset($data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['customer']);
                            unset($data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['cashier']);

                            $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['bid'] = $terminalTransactionDetail->bid;
                            $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['created_at'] =
                                ! is_null($terminalTransactionDetail->created_at)
                                    ? Carbon::parse($terminalTransactionDetail->created_at)->format('Y-m-d H:i:s')
                                    : null;
                            $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['updated_at'] =
                                ! is_null($terminalTransactionDetail->updated_at)
                                    ? Carbon::parse($terminalTransactionDetail->updated_at)->format('Y-m-d H:i:s')
                                    : null;
                            $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['deleted_at'] =
                                ! is_null($terminalTransactionDetail->deleted_at)
                                    ? Carbon::parse($terminalTransactionDetail->deleted_at)->format('Y-m-d H:i:s')
                                    : null;

                            if (isset($officialReceipt->discount)) {
                                foreach ($officialReceipt->discount as $discountIndex => $discount) {
                                    $discount = (object) $discount;

                                    $discountData = [
                                        'transaction_product_bid' => $terminalTransactionDetail->bid,
                                        'discount_bid' => $discount->id,
                                        'title' => $discount->title,
                                        'total' => $discount->total,
                                        'amount_discount' => $discount->amount_discount,
                                        'vat_deduct' => $discount->vat_deduct,
                                        'mandated' => $discount->mandated
                                    ];

                                    $terminalTransactionDetailDiscount = $terminalTransactionDetail->discounts()->create($discountData);

                                    foreach ($discountData as $discountDatumKey => $discountDatum) {
                                        $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['discount'][$discountIndex][$discountDatumKey] = $discountDatum;
                                    }

                                    $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['discount'][$discountIndex]['bid'] = $terminalTransactionDetailDiscount->bid;
                                    $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['discount'][$discountIndex]['created_at'] =
                                        ! is_null($terminalTransactionDetailDiscount->created_at)
                                            ? Carbon::parse($terminalTransactionDetailDiscount->created_at)->format('Y-m-d H:i:s')
                                            : null;
                                    $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['discount'][$discountIndex]['updated_at'] =
                                        ! is_null($terminalTransactionDetailDiscount->updated_at)
                                            ? Carbon::parse($terminalTransactionDetailDiscount->updated_at)->format('Y-m-d H:i:s')
                                            : null;
                                    $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['discount'][$discountIndex]['deleted_at'] =
                                        ! is_null($terminalTransactionDetailDiscount->deleted_at)
                                            ? Carbon::parse($terminalTransactionDetailDiscount->deleted_at)->format('Y-m-d H:i:s')
                                            : null;
                                }
                            }

                            if (isset($officialReceipt->payment_method)) {
                                foreach ($officialReceipt->payment_method as $paymentMethodIndex => $paymentMethod) {
                                    $paymentMethod = (object) $paymentMethod;

                                    $paymentMethodData = [
                                        'transaction_detail_bid' => $terminalTransactionDetail->bid,
                                        'title' => $paymentMethod->title,
                                        'total' => $paymentMethod->total,
                                        'account_number' => $paymentMethod->account_number,
                                    ];

                                    $paymentMethodCreated = $terminalTransactionDetail->paymentMethods()->create($paymentMethodData);

                                    foreach ($paymentMethodData as $paymentMethodDatumKey => $paymentMethodDatum) {
                                        $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['payment_method'][$paymentMethodIndex][$paymentMethodDatumKey] = $paymentMethodDatum;
                                    }

                                    $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['payment_method'][$paymentMethodIndex]['bid'] = $paymentMethodCreated->bid;
                                    $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['payment_method'][$paymentMethodIndex]['created_at'] =
                                        ! is_null($paymentMethodCreated->created_at)
                                            ? Carbon::parse($paymentMethodCreated->created_at)->format('Y-m-d H:i:s')
                                            : null;
                                    $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['payment_method'][$paymentMethodIndex]['updated_at'] =
                                        ! is_null($paymentMethodCreated->updated_at)
                                            ? Carbon::parse($paymentMethodCreated->updated_at)->format('Y-m-d H:i:s')
                                            : null;
                                    $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['payment_method'][$paymentMethodIndex]['deleted_at'] =
                                        ! is_null($paymentMethodCreated->deleted_at)
                                            ? Carbon::parse($paymentMethodCreated->deleted_at)->format('Y-m-d H:i:s')
                                            : null;
                                }
                            }

                            if (isset($officialReceipt->product)) {
                                foreach ($officialReceipt->product as $productIndex => $product) {
                                    $product = (object) $product;

                                    $productData = [
                                        'transaction_detail_bid' => $terminalTransactionDetail->bid,
                                        'product_bid' => $product->id,
                                        'name' => $product->name,
                                        'description' => $product->description,
                                        'long_description' => $product->long_description,
                                        'menu_code' => $product->menu_code,
                                        'category_bid' => $product->category['id'],
                                        'category_name' => $product->category['name'],
                                        'quantity' => $product->quantity,
                                        'tax_percentage' => $product->tax_percentage,
                                        'order_type_id' => $product->order_type['id'],
                                        'order_type_name' => $product->order_type['name'],
                                        'is_free' => $product->is_free,
                                        'is_vatable' => $product->is_vatable,
                                        'original_price' => $product->original_price,
                                        'price' => $product->price,
                                        'total_addon' => $product->total_addon,
                                        'total_amount' => $product->total_amount,
                                        'amount_discount' => $product->amount_discount,
                                        'vatable_sales' => $product->vatable_sales,
                                        'zero_rated_sales' => $product->zero_rated_sales,
                                        'tax' => $product->tax,
                                        'vat_deduct' => $product->vat_deduct,
                                        'vat_exempt' => $product->vat_exempt,
                                        'split_number' => $product->split_number,
                                    ];

                                    $terminalTransactionDetailProduct = $terminalTransactionDetail->products()->create($productData);

                                    foreach ($productData as $productDatumKey => $productDatum) {
                                        $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex][$productDatumKey] = $productDatum;
                                    }

                                    unset($data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['category']);
                                    unset($data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['order_type']);

                                    $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['bid'] = $terminalTransactionDetailProduct->bid;
                                    $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['created_at'] =
                                        ! is_null($terminalTransactionDetailProduct->created_at)
                                            ? Carbon::parse($terminalTransactionDetailProduct->created_at)->format('Y-m-d H:i:s')
                                            : null;
                                    $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['updated_at'] =
                                        ! is_null($terminalTransactionDetailProduct->updated_at)
                                            ? Carbon::parse($terminalTransactionDetailProduct->updated_at)->format('Y-m-d H:i:s')
                                            : null;
                                    $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['deleted_at'] =
                                        ! is_null($terminalTransactionDetailProduct->deleted_at)
                                            ? Carbon::parse($terminalTransactionDetailProduct->deleted_at)->format('Y-m-d H:i:s')
                                            : null;

                                    if (isset($product->price_override_details)) {
                                        if ($product->price_override_details['price']) {
                                            $priceOverrideData = [
                                                'transaction_detail_bid' => $terminalTransactionDetail->bid,
                                                'transaction_product_bid' => $terminalTransactionDetailProduct->bid,
                                                'product_bid' => $product->id,
                                                'product_name' => $product->name,
                                                'product_description' => $product->description,
                                                'product_code' => $product->menu_code,
                                                'old_price' => $product->original_price,
                                                'new_price' => $product->price_override_details['price'],
                                                'quantity' => $product->quantity,
                                                'approved_by' => $product->price_override_details['approved_by'] ?? null,
                                                'approved_date' => $product->price_override_details['approved_date'] ?? null,
                                            ];

                                            $priceOverrideDataCreated = $terminalTransactionDetail->priceOverride()->create($priceOverrideData);

                                            foreach ($priceOverrideData as $priceOverrideDatumKey => $priceOverrideDatum) {
                                                $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['price_override_details'][$priceOverrideDatumKey] = $priceOverrideDatum;
                                            }

                                            $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['price_override_details']['bid'] = $priceOverrideDataCreated->bid;
                                        } else {
                                            $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['price_override_details'] = null;
                                        }
                                    }

                                    if (isset($product->addon)) {
                                        foreach ($product->addon as $addonIndex => $addon) {
                                            $addon = (object) $addon;

                                            $addonData = [
                                                'transaction_detail_bid' => $terminalTransactionDetailProduct->bid,
                                                'product_bid' => $addon->product_bid,
                                                'name' => $addon->name,
                                                'quantity' => $addon->quantity,
                                                'tax_percentage' => $addon->tax_percentage,
                                                'original_price' => $addon->original_price,
                                                'price' => $addon->price,
                                                'total_amount' => $addon->total_amount,
                                                'vatable_sales' => $addon->vatable_sales,
                                                'zero_rated_sales' => $addon->zero_rated_sales,
                                                'tax' => $addon->tax,
                                                'vat_exempt' => $addon->vat_exempt,
                                            ];

                                            $terminalTransactionDetailAddon = $terminalTransactionDetailProduct->addons()->create($addonData);

                                            foreach ($addonData as $addonDatumKey => $addonDatum) {
                                                $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['addon'][$addonIndex][$addonDatumKey] = $addonDatum;
                                            }

                                            $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['addon'][$addonIndex]['bid'] = $terminalTransactionDetailAddon->bid;
                                            $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['addon'][$addonIndex]['created_at'] =
                                                ! is_null($terminalTransactionDetailAddon->created_at)
                                                    ? Carbon::parse($terminalTransactionDetailAddon->created_at)->format('Y-m-d H:i:s')
                                                    : null;
                                            $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['addon'][$addonIndex]['updated_at'] =
                                                ! is_null($terminalTransactionDetailAddon->updated_at)
                                                    ? Carbon::parse($terminalTransactionDetailAddon->updated_at)->format('Y-m-d H:i:s')
                                                    : null;
                                            $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['addon'][$addonIndex]['deleted_at'] =
                                                ! is_null($terminalTransactionDetailAddon->deleted_at)
                                                    ? Carbon::parse($terminalTransactionDetailAddon->deleted_at)->format('Y-m-d H:i:s')
                                                    : null;
                                        }
                                    } else {
                                        $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['addon'] = [];
                                    }

                                    if (isset($product->discount)) {
                                        foreach ($product->discount as $discountIndex => $discount) {
                                            $discount = (object) $discount;

                                            $discountData = [
                                                'transaction_product_bid' => $terminalTransactionDetailProduct->bid,
                                                'discount_bid' => $discount->discount_bid,
                                                'title' => $discount->title,
                                                'total' => $discount->total,
                                                'amount_discount' => $discount->amount_discount,
                                                'vat_deduct' => $discount->vat_deduct,
                                                'vat_exempt' => $discount->vat_exempt,
                                                'mandated' => (int) $discount->mandated
                                            ];

                                            $terminalTransactionDiscount = $terminalTransactionDetailProduct->discounts()->create($discountData);

                                            foreach ($discountData as $discountDatumKey => $discountDatum) {
                                                $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['discount'][$discountIndex][$discountDatumKey] = $discountDatum;
                                            }

                                            $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['discount'][$discountIndex]['bid'] = $terminalTransactionDiscount->bid;
                                            $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['discount'][$discountIndex]['created_at'] =
                                                ! is_null($terminalTransactionDiscount->created_at)
                                                    ? Carbon::parse($terminalTransactionDiscount->created_at)->format('Y-m-d H:i:s')
                                                    : null;
                                            $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['discount'][$discountIndex]['updated_at'] =
                                                ! is_null($terminalTransactionDiscount->updated_at)
                                                    ? Carbon::parse($terminalTransactionDiscount->updated_at)->format('Y-m-d H:i:s')
                                                    : null;
                                            $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['discount'][$discountIndex]['deleted_at'] =
                                                ! is_null($terminalTransactionDiscount->deleted_at)
                                                    ? Carbon::parse($terminalTransactionDiscount->deleted_at)->format('Y-m-d H:i:s')
                                                    : null;
                                        }
                                    } else {
                                        $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'][$productIndex]['discount'] = [];
                                    }
                                }
                            } else {
                                $data[$headIndex][$datumIndex]['official_receipt'][$officialReceiptIndex]['product'] = [];
                            }
                        }
                    } else {
                        $data[$headIndex][$datumIndex]['official_receipt'] = [];
                    }
                }
            }

            return $data;
        });
    }
}
