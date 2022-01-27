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
        'tax_percentage',
        'original_price',
        'price',
        'total_amount',
        'vatable_sales',
        'zero_rated_sales',
        'tax',
        'vat_exempt',
        'remarks',
    ];

    protected $casts = [
        'bid' => 'string',
        'transaction_product_bid' => 'string',
        'product_bid' => 'string',
    ];

    public function transactionProduct()
    {
        return $this->belongsTo(CDISTerminalTransactionProduct::class, 'transaction_product_bid', 'bid');
    }
}
