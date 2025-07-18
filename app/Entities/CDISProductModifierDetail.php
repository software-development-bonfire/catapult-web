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
        'modifier_for_product_pricing_type_bid',
        'product_uom_bid',
        'product_branch_price_bid',
        'quantity',
        'is_default',
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

    public function syncDetails()
    {
        return (object) array(
            'code' => '',
            'group' => null,
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => json_encode([$this->head_bid, $this->product_uom_bid, $this->product_branch_price_bid]),
            'reference_table' => json_encode(['cdis_product_modifier', 'cdis_product_uom_packaging', 'cdis_product_branch_price'])
        );
    }
}
