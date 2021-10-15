<?php

namespace App\Entities;

class CDISPriceOverride extends BaseModel
{
    protected $table = 'cdis_price_override';

    public $timestamps = false;

    protected $fillable = [
        'transaction_detail_bid',
        'transaction_product_bid',
        'product_bid',
        'product_name',
        'product_description',
        'product_code',
        'old_price',
        'new_price',
        'quantity',
        'approved_by',
        'approved_date'
    ];

    protected $casts = [
        'bid' => 'string',
        'transaction_detail_bid' => 'string',
        'transaction_product_bid' => 'string',
    ];
}
