<?php

namespace App\Entities;

class CDISTerminalTransactionProduct extends Base
{
    protected $table = 'cdis_terminal_transaction_product';

    protected $fillable = [
        'transaction_detail_bid',
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
        'is_additional',
        'is_vatable',
        'original_price',
        'price',
        'total_addon',
        'total_amount',
        'entire_discount',
        'amount_discount',
        'vatable_sales',
        'zero_rated_sales',
        'tax',
        'vat_deduct',
        'vat_exempt',
        'split_number',
        'remarks',
        'special_request',
        'supervisor_bid',
        'supervisor_name',
        'created_at',
        'sent_at',
    ];

    protected $casts = [
        'bid' => 'string',
        'transaction_detail_bid' => 'string',
        'product_bid' => 'string',
        'category_bid' => 'string',
        'sent_at' => 'datetime',
    ];

    public function detail()
    {
        return $this->belongsTo(CDISTerminalTransactionDetail::class, 'transaction_detail_bid', 'bid');
    }

    public function addons()
    {
        return $this->hasMany(CDISTerminalTransactionAddon::class, 'transaction_product_bid', 'bid');
    }

    public function discounts()
    {
        return $this->hasMany(CDISTerminalTransactionDiscount::class, 'transaction_product_bid', 'bid');
    }
}
