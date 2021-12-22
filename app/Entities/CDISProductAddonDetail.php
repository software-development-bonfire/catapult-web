<?php

namespace App\Entities;

class CDISProductAddonDetail extends BaseModel
{
    protected $table = 'cdis_product_addon_detail';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'head_bid',
        'product_uom_bid',
        'product_branch_price_bid',
        'quantity',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'product_pricing_type_bid' => 'string',
        'product_uom_bid' => 'string',
    ];

    public function head()
    {
        return $this->belongsTo(CDISProductAddon::class, 'head_bid', 'bid');
    }
}
