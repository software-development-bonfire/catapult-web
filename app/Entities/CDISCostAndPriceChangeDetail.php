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

    public function costAndPriceChange()
    {
        return $this->belongsTo(CDISCostAndPriceChange::class, 'head_bid', 'bid');
    }

    public function productUomPackaging()
    {
        return $this->belongsTo(CDISProductUomPackaging::class, 'product_uom_bid', 'bid');
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
