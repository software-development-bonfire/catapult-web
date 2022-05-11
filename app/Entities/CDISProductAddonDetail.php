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
        'product_branch_price_bid' => 'string',
        'product_uom_bid' => 'string',
    ];

    public function productUomPackaging()
    {
        return $this->belongsTo(CDISProductUomPackaging::class, 'product_uom_bid', 'bid');
    }

    public function productBranchPrice()
    {
        return $this->belongsTo(CDISProductBranchPrice::class, 'product_branch_price_bid', 'bid');
    }

    public function productAddon()
    {
        return $this->belongsTo(CDISProductAddon::class, 'head_bid', 'bid');
    }
}
