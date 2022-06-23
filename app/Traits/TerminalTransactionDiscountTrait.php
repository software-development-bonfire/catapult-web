<?php

namespace App\Traits;

use App\Entities\CDISTerminalTransactionDetail;
use App\Entities\CDISTerminalTransactionProduct;
use App\Entities\CDISTerminalTransactionDiscount;
use App\Entities\CDISTerminalTransactionAddon;
use App\Enums\UsageType;

/**
 * Trait TerminalTransactionDiscountTrait
 * @package App\Traits
 */
trait TerminalTransactionDiscountTrait
{
    public function deleteRelatedDiscounts($terminalTransaction) {
        $terminalTransactions = $terminalTransaction->get();
        foreach($terminalTransactions as $transaction){
            $transactionDetails = CDISTerminalTransactionDetail::where('transaction_head_bid', '=', $transaction->bid)->get();
            foreach($transactionDetails as $detail){
                $transactionProducts = CDISTerminalTransactionProduct::where('transaction_detail_bid', '=', $detail->bid)->get();
                foreach($transactionProducts as $product){
                    $discounts = CDISTerminalTransactionDiscount::where('transaction_product_bid', '=', $product->bid)
                    ->where('usage_type', '=', UsageType::PRODUCT)->get();
                    if($discounts){
                        $discounts->each(function($discount){
                            $discount->delete();
                        });
                    }

                    $addons = CDISTerminalTransactionAddon::where('transaction_product_bid', '=', $product->bid)->get();
                    foreach($addons as $addon){
                        $discounts = CDISTerminalTransactionDiscount::where('transaction_product_bid', '=', $addon->bid)
                        ->where('usage_type', '!=', UsageType::PRODUCT)->get();
                        if($discounts){
                            $discounts->each(function($discount){
                                $discount->delete();
                            });
                        }
                    }
                }
            }
        }
        
    }
}
