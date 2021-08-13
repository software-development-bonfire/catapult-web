<?php

namespace App\Entities;

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
    ];

    protected $casts = [
        'bid' => 'string',
        'transaction_product_bid' => 'string',
        'discount_bid' => 'string',
    ];
}
