<?php

namespace App\Entities;

class CDISTerminalTransactionAddon extends Base
{
    protected $table = 'cdis_terminal_transaction_addon';

    protected $fillable = [
        'transaction_product_bid',
        'product_bid',
        'name',
        'description',
        'long_description',
        'menu_code',
        'category_bid',
        'category_name',
        'quantity',
        'tax_percentage',
        'order_type_id',
        'order_type_name',
        'is_free',
        'is_vatable',
        'original_price',
        'price',
        'total_amount',
        'vatable_sales',
        'zero_rated_sales',
        'amount_discount',
        'tax',
        'vat_deduct',
        'vat_exempt',
        'split_number',
        'remarks',
        'usage_type',
        'supervisor_bid',
        'supervisor_name'
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
