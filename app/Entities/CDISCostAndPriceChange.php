<?php

namespace App\Entities;

class CDISCostAndPriceChange extends BaseModel
{
    protected $table = 'cdis_cost_and_price_change';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'code',
        'pricing_type',
        'type',
        'vendor_bid',
        'effective_at',
        'expires_at',
        'status',
        'assessed_by',
        'assessed_at',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'bid' => 'string',
        'vendor_bid' => 'string',
        'category_bid' => 'string',
        'code' => 'string',
        'assessed_by' => 'string',
        'approved_by' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];
}
