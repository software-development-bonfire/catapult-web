<?php

namespace App\Entities;

class CDISProductModifierDetail extends BaseModel
{
    protected $table = 'cdis_product_modifier_detail';

    protected $primaryKey = 'bid';

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

    public function productModifier()
    {
        return $this->belongsTo(CDISProductModifier::class, 'head_bid', 'bid');
    }

    public function productUomPackaging()
    {
        return $this->belongsTo(CDISProductUomPackaging::class, 'product_uom_bid', 'bid');
    }

    public function productBranchPrice()
    {
        return $this->belongsTo(CDISProductBranchPrice::class, 'product_branch_price_bid', 'bid');
    }
}
