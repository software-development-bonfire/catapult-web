<?php

namespace App\Entities;

class CDISTerminalTransactionDetail extends Base
{
    protected $table = 'cdis_terminal_transaction_detail';

    protected $fillable = [
        'transaction_head_bid',
        'or_number',
        'split_number',
        'total',
        'discount_amount',
        'free_items_amount',
        'quantity',
        'original_amount',
        'vat_deduct_amount',
        'vat_exempt_amount',
        'local_tax_amount',
        'tax_amount',
        'service_charge',
        'vatable_sales',
        'zero_rated_sales',
        'eligible_amount_to_earn_points',
        'total_tender',
        'customer_type',
        'customer_bid',
        'customer_name',
        'customer_address',
        'cashier_bid',
        'cashier_name',
        'remarks',
    ];

    protected $casts = [
        'bid' => 'string',
        'transaction_head_bid' => 'string',
        'cashier_bid' => 'string',
    ];
}
