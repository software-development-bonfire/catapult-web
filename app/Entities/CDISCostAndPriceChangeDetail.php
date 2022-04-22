<?php

namespace App\Entities;

class CDISCostAndPriceChangeDetail extends BaseModel
{
    protected $table = 'cdis_cost_and_price_change_detail';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'head_bid',
        'product_uom_bid',
        'branch_bid',
        'pricing_type',
        'product_pricing_type_bid',
        'pricing_head_bid',
        'old_value',
        'new_value',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'product_uom_bid' => 'string',
        'branch_bid' => 'string',
        'product_pricing_type_bid' => 'string',
        'pricing_head_bid' => 'string',
    ];
}
