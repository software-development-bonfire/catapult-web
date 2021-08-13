<?php

namespace App\Entities;

class CDISTerminalTransactionDetailDiscount extends Base
{
    protected $table = 'cdis_terminal_transaction_detail_discount';

    protected $fillable = [
        'transaction_detail_bid',
        'discount_bid',
        'title',
        'total',
        'amount_discount',
        'vat_deduct',
        'mandated',
        'remarks',
    ];

    protected $casts = [
        'bid' => 'string',
        'transaction_detail_bid' => 'string',
        'discount_bid' => 'string',
    ];
}
