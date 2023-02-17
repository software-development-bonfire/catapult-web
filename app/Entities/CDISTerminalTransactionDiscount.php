<?php

namespace App\Entities;

use App\Enums\UsageType;

class CDISTerminalTransactionDiscount extends Base
{
    protected $table = 'cdis_terminal_transaction_discount';

    protected $fillable = [
        'transaction_product_bid',
        'discount_bid',
        'title',
        'total',
        'amount_discount',
        'vat_deduct',
        'vat_exempt',
        'eligible_amount_to_earn_points',
        'mandated',
        'remarks',
        'usage_type',
    ];

    protected $casts = [
        'bid' => 'string',
        'transaction_product_bid' => 'string',
        'discount_bid' => 'string',
    ];

    public function product()
    {
        return $this->belongsTo(CDISTerminalTransactionProduct::class, 'transaction_product_bid', 'bid')->where('usage_type', '=', UsageType::PRODUCT);
    }

    public function addon()
    {
        return $this->belongsTo(CDISTerminalTransactionAddon::class, 'transaction_product_bid', 'bid')->where('usage_type', '=', UsageType::ADDON);
    }
}
