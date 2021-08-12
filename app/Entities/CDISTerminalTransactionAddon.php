<?php

namespace App\Entities;

class CDISTerminalTransactionAddon extends Base
{
    protected $table = 'cdis_terminal_transaction_addon';

    protected $fillable = [
        'transaction_product_bid',
        'product_bid',
        'name',
        'quantity',
        'original_price',
        'price',
        'total_amount',
        'remarks',
    ];

    protected $casts = [
        'bid' => 'string',
        'transaction_product_bid' => 'string',
        'product_bid' => 'string',
    ];
}
