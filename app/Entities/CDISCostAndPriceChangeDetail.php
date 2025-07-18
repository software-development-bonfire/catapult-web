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
        'old_discount_type',
        'old_discount_1',
        'old_discount_2',
        'old_discount_3',
        'old_discount_4',
        'old_adjustment',
        'old_net_amount',
        'old_percentage_change',
        'new_value',
        'discount_type',
        'discount_1',
        'discount_2',
        'discount_3',
        'discount_4',
        'adjustment',
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

    public function costAndPriceChange()
    {
        return $this->belongsTo(CDISCostAndPriceChange::class, 'head_bid', 'bid');
    }

    public function productUomPackaging()
    {
        return $this->belongsTo(CDISProductUomPackaging::class, 'product_uom_bid', 'bid');
    }

    public function productPricingType()
    {
        return $this->belongsTo(CDISProductPricingType::class, 'product_pricing_type_bid', 'bid');
    }

    public function branch()
    {
        return $this->belongsTo(CDISBranch::class, 'branch_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => '',
            'group' => null,
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => json_encode([$this->product_uom_bid, $this->branch_bid, $this->product_pricing_type_bid, $this->product_pricing_type_bid]),
            'reference_table' => json_encode(['cdis_product_uom_packaging', 'cdis_branch', 'cdis_product_pricing_type', 'cdis_pricing_head'])
        );
    }
}
